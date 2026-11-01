<?php

namespace App\Http\Controllers;

use App\Services\CompanyMailer;
use App\Services\MailOAuth;
use App\Services\MailSettings;
use App\Services\SendLog;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsProviders;
use App\Services\SmsSettings;
use App\Services\UrlGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/** Email and SMS services: which are connected, which is in use, and the test sends. */
class ConfigController extends Controller
{
    public function email(Request $request)
    {
        return $this->emailPayload($request->user()->company);
    }

    /** Sender details, and which connected service is in use for sending. */
    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email',
            'reply_to' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'active' => ['required', Rule::in(array_merge(['default'], MailSettings::keys()))],
        ]);

        $company = $request->user()->company;
        $conns = MailSettings::connections($company);
        if ($data['active'] !== 'default' && ! isset($conns[$data['active']])) {
            return response()->json(['message' => 'Connect '.ucfirst($data['active']).' first, then choose it for sending.'], 422);
        }
        $columns = collect($data)->only('from_name', 'from_email', 'reply_to', 'address')->all();
        if (in_array($data['active'], MailOAuth::PROVIDERS, true) && ! empty($conns[$data['active']]['email'])) {
            $columns['from_email'] = $conns[$data['active']]['email']; // Google / Microsoft only send as the connected mailbox
        }

        MailSettings::update($company, fn ($c, $active) => [$c, $data['active']], $columns);

        return $this->emailPayload($company->fresh());
    }

    /** Saves the login for one SMTP service. It stays saved while another service is the one in use. */
    public function saveConnection(Request $request, string $key)
    {
        abort_unless(in_array($key, MailSettings::SMTP, true), 404);
        $data = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|in:none,tls,ssl',
            'activate' => 'nullable|boolean', // also make it the service in use
        ]);

        try {
            UrlGuard::assertPublicHost($data['host']);
        } catch (Throwable $e) {
            return response()->json(['message' => 'SMTP host: '.$e->getMessage()], 422);
        }

        $company = $request->user()->company;
        $old = MailSettings::connections($company)[$key] ?? [];
        $conn = [
            'host' => $data['host'],
            'port' => $data['port'] ?? 587,
            'encryption' => $data['encryption'] ?? 'tls',
            'username' => $data['username'] ?? null,
            // blank = keep the stored password
            'password' => filled($data['password'] ?? null) ? $data['password'] : ($old['password'] ?? null),
        ];
        MailSettings::update($company, fn ($c, $active) => [array_merge($c, [$key => $conn]), ! empty($data['activate']) ? $key : $active]);

        return $this->emailPayload($company->fresh());
    }

    /** Forgets one connection. If it was the one in use, sending goes back to the default server. */
    public function disconnect(Request $request, string $key)
    {
        abort_unless(in_array($key, MailSettings::keys(), true), 404);
        $company = $request->user()->company;

        MailSettings::update($company, function ($c, $active) use ($key) {
            unset($c[$key]);

            return [$c, $active === $key ? 'default' : $active];
        });

        return $this->emailPayload($company->fresh());
    }

    /**
     * Which ready-made SMTP service fits this From address? Well-known mailbox domains are matched directly; for a company's own
     * domain we look at where its mail is hosted (MX records), e.g. Google Workspace or Microsoft 365.
     */
    public function suggest(Request $request)
    {
        $email = $request->validate(['email' => 'required|email|max:255'])['email'];
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        $known = [
            'gmail.com' => 'gmail', 'googlemail.com' => 'gmail',
            'outlook.com' => 'outlook', 'hotmail.com' => 'outlook', 'live.com' => 'outlook', 'msn.com' => 'outlook',
            'yahoo.com' => 'yahoo', 'ymail.com' => 'yahoo', 'rocketmail.com' => 'yahoo',
            'zoho.com' => 'zoho', 'zohomail.com' => 'zoho',
        ];
        if (isset($known[$domain])) {
            return ['preset' => $known[$domain], 'reason' => "@{$domain} is a ".ucfirst($known[$domain]).' address.'];
        }

        // MX host fragment => [service, what it means]
        $hosting = [
            'google.com' => ['gmail', 'Google Workspace'], 'googlemail.com' => ['gmail', 'Google Workspace'],
            'outlook.com' => ['outlook', 'Microsoft 365'], 'zoho.' => ['zoho', 'Zoho Mail'],
            'yahoodns.net' => ['yahoo', 'Yahoo Mail'], 'mailgun.org' => ['mailgun', 'Mailgun'],
        ];
        $mx = array_map(fn ($r) => strtolower($r['target'] ?? ''), @dns_get_record($domain, DNS_MX) ?: []);
        foreach ($mx as $host) {
            foreach ($hosting as $needle => [$preset, $label]) {
                if (str_contains($host, $needle)) {
                    return ['preset' => $preset, 'reason' => "{$domain} receives its mail with {$label}, so you can send through it too."];
                }
            }
        }

        return ['preset' => null, 'reason' => $mx ? "{$domain} uses its own mail host ({$mx[0]}). Ask your host for its SMTP details and choose Custom server, or use a sending service such as SendGrid, Mailgun or Amazon SES." : "We couldn't tell where {$domain} hosts its mail. Pick the service you use, or choose Custom server."];
    }

    /** Sends a real message with the saved settings so problems show up immediately. */
    public function testEmail(Request $request)
    {
        $to = $request->validate(['to' => 'required|email'])['to'];
        $company = $request->user()->company;

        $subject = 'Test email from '.$company->name;
        $html = '<p>This is a test email from <b>'.e($company->name).'</b>. Your email configuration works.</p>';

        try {
            SendLog::run($company, 'email', $to, $subject, $html, 'test', fn () => CompanyMailer::for($company)->html($html, function ($m) use ($to, $company, $subject) {
                $m->to($to)->subject($subject);
                if ($company->from_email) {
                    $m->from($company->from_email, $company->from_name ?: $company->name);
                }
            }));
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Sending failed: '.mb_substr($e->getMessage(), 0, 300),
                'hint' => $this->explainMailError($e->getMessage(), MailSettings::active($company)['conn'] ?? []),
            ], 422);
        }

        return ['ok' => true, 'message' => "Test email sent to {$to}."];
    }

    /** Turns a mail-server error into what the person should actually do about it (null when we have no advice). */
    protected function explainMailError(string $error, array $email): ?string
    {
        $host = strtolower((string) ($email['host'] ?? ''));
        $rejected = str_contains($error, '535') || str_contains($error, 'Failed to authenticate') || str_contains($error, '534');

        if ($rejected && (str_contains($host, 'gmail') || str_contains($error, '5.7.8') || str_contains($error, 'gmail'))) {
            return 'Gmail did not accept that password. Gmail does not allow your normal password here: turn on 2-Step Verification for the Google account, create an "App password" (myaccount.google.com → Security → App passwords) and paste that 16-character code as the password. The username must be the full Gmail address. For a Google Workspace account, the administrator may also need to allow app passwords.';
        }
        if ($rejected && str_contains($host, 'office365')) {
            return 'Microsoft 365 refused the sign-in. Check the username is the full mailbox address, that "Authenticated SMTP" is enabled for that mailbox, and use an app password if the account has two-step sign-in.';
        }
        if ($rejected) {
            return 'The mail server rejected the username or password. Re-enter them (the password field is blank when you only want to keep the saved one) and check whether your provider needs an app password or an API key instead of your normal password.';
        }
        if (preg_match('/connection (could not be established|refused|timed out)|timed out|getaddrinfo|php_network/i', $error)) {
            return 'Could not reach the mail server. Check the server name and port, and that the encryption matches the port (587 with TLS/STARTTLS, or 465 with SSL).';
        }
        if (stripos($error, 'starttls') !== false || stripos($error, 'ssl') !== false || stripos($error, 'tls') !== false) {
            return 'The encryption setting does not match the server. Port 587 normally uses TLS/STARTTLS and port 465 uses SSL.';
        }
        if (preg_match('/5\.7\.1|relay|not allowed|sender|from address/i', $error)) {
            return 'The server refused the sender address. Set From email to an address your mail provider lets you send from (for Gmail, Outlook, Yahoo and Zoho that is the account you sign in with).';
        }

        return null;
    }

    // ------------------------------------------------------------------ SMS services (same idea as email: connect many, one in use)

    public function sms(Request $request)
    {
        return $this->smsPayload($request->user()->company);
    }

    /** Which connected SMS service is in use ("log" = test mode, nothing is sent). */
    public function updateSms(Request $request)
    {
        $data = $request->validate(['active' => ['required', Rule::in(array_merge(['log'], SmsProviders::keys()))]]);
        $company = $request->user()->company;
        if ($data['active'] !== 'log' && ! isset(SmsSettings::connections($company)[$data['active']])) {
            return response()->json(['message' => 'Connect '.SmsProviders::SPECS[$data['active']]['name'].' first, then choose it for sending.'], 422);
        }
        SmsSettings::update($company, fn ($c, $active) => [$c, $data['active']]);

        return $this->smsPayload($company->fresh());
    }

    /** Saves the login for one SMS service. It stays saved while another service is the one in use. */
    public function saveSmsConnection(Request $request, string $key)
    {
        abort_unless(in_array($key, SmsProviders::keys(), true), 404);
        $company = $request->user()->company;
        $old = SmsSettings::connections($company)[$key] ?? [];
        $secrets = SmsProviders::secretFields($key);

        $rules = ['activate' => 'nullable|boolean'];
        foreach (SmsProviders::SPECS[$key]['fields'] as $f) {
            $blankKeepsOld = ! empty($f['secret']) && ! empty($old[$f['key']]);
            $rules[$f['key']] = ($blankKeepsOld || $f['key'] === 'region' ? 'nullable' : 'required').'|string|max:255';
        }
        $data = $request->validate($rules);

        $conn = [];
        foreach (SmsProviders::SPECS[$key]['fields'] as $f) {
            $v = $data[$f['key']] ?? null;
            $conn[$f['key']] = in_array($f['key'], $secrets, true) && ! filled($v) ? ($old[$f['key']] ?? null) : $v; // blank secret = keep the stored one
        }
        SmsSettings::update($company, fn ($c, $active) => [array_merge($c, [$key => $conn]), ! empty($data['activate']) ? $key : $active]);

        return $this->smsPayload($company->fresh());
    }

    /** Forgets one SMS connection. If it was the one in use, sending goes back to test mode. */
    public function disconnectSms(Request $request, string $key)
    {
        abort_unless(in_array($key, SmsProviders::keys(), true), 404);
        $company = $request->user()->company;
        SmsSettings::update($company, function ($c, $active) use ($key) {
            unset($c[$key]);

            return [$c, $active === $key ? 'log' : $active];
        });

        return $this->smsPayload($company->fresh());
    }

    protected function smsPayload($company): array
    {
        return [
            'active' => SmsSettings::activeKey($company),
            'connections' => SmsSettings::payload($company), // secrets are only ever shown as "…_set"
            'providers' => collect(SmsProviders::SPECS)->map(fn ($p) => ['name' => $p['name'], 'fields' => array_map(fn ($f) => collect($f)->only('key', 'label', 'secret', 'placeholder')->all(), $p['fields'])])->all(),
        ];
    }

    public function testSms(Request $request, SmsManager $sms)
    {
        $to = $request->validate(['to' => 'required|string|max:30'])['to'];

        $company = $request->user()->company;
        $text = 'Test SMS from '.$company->name.'. Your SMS configuration works.';

        try {
            SendLog::run($company, 'sms', $to, null, $text, 'test', fn () => $sms->forCompany($company)->send($to, $text));
        } catch (Throwable $e) {
            return response()->json(['message' => 'Sending failed: '.mb_substr($e->getMessage(), 0, 300)], 422);
        }

        return ['ok' => true, 'message' => "Test SMS sent to {$to}."];
    }

    protected function emailPayload($company): array
    {
        return $company->only('from_name', 'from_email', 'reply_to', 'address') + [
            'active' => MailSettings::activeKey($company),
            'connections' => MailSettings::payload($company), // which services are connected (never a password or token)
            'oauth' => collect(MailOAuth::PROVIDERS)->mapWithKeys(fn ($p) => [$p => ['configured' => MailOAuth::configured($p)]])->all(),
        ];
    }
}
