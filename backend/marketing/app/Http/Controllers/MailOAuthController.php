<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\MailOAuth;
use App\Services\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/** One-click "connect your Google / Microsoft mailbox" for sending email. */
class MailOAuthController extends Controller
{
    /** Step 1 (signed-in user): where to send the browser to approve access. */
    public function start(Request $request, string $provider)
    {
        $this->known($provider);
        abort_unless(MailOAuth::configured($provider), 422, 'This sign-in is not set up on the platform yet. Ask the platform administrator to add its OAuth client id and secret.');

        $state = Crypt::encryptString(json_encode([
            'company' => $request->user()->company_id, 'provider' => $provider, 'exp' => now()->addMinutes(10)->timestamp,
        ]));

        return ['url' => MailOAuth::authorizeUrl($provider, $state)];
    }

    /** Step 2 (the provider sends the browser back here): store the connection and return to the settings page. */
    public function callback(Request $request, string $provider)
    {
        $this->known($provider);
        $back = rtrim(config('services.mail_oauth.frontend_url'), '/').'/settings/email';

        try {
            $state = json_decode(Crypt::decryptString((string) $request->query('state')), true);
            abort_unless(($state['provider'] ?? null) === $provider && ($state['exp'] ?? 0) >= now()->timestamp, 400);
            $company = Company::findOrFail($state['company']);
        } catch (Throwable) {
            return redirect($back.'?oauth_error='.urlencode('The sign-in link expired. Please try again.'));
        }
        if ($request->query('error') || ! $request->query('code')) {
            return redirect($back.'?oauth_error='.urlencode('Access was not approved.'));
        }

        try {
            $conn = MailOAuth::exchange($provider, (string) $request->query('code'));
        } catch (Throwable $e) {
            return redirect($back.'?oauth_error='.urlencode(mb_substr($e->getMessage(), 0, 200)));
        }

        // every other connection is kept; this one becomes the one in use only if nothing else was
        $becomesActive = MailSettings::activeKey($company) === 'default';
        MailSettings::update(
            $company,
            fn ($conns, $active) => [array_merge($conns, [$provider => ['email' => $conn['email'], 'refresh_token' => $conn['refresh_token']]]), $becomesActive ? $provider : $active],
            $becomesActive ? ['from_email' => $conn['email']] : [], // these providers only let you send as the connected mailbox
        );

        return redirect($back.'?connected='.$provider);
    }

    protected function known(string $provider): void
    {
        abort_unless(in_array($provider, MailOAuth::PROVIDERS, true), 404);
    }
}
