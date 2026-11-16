<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\UrlGuard;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * One online platform whose customers can be imported as People.
 *
 * A provider knows how to check a connection (test) and how to read customers one page at a time (customers), and
 * answers in one common shape, so the importer does not care where a customer came from:
 *
 *   ['id' => '123', 'email' => 'ann@x.com'|null, 'first_name' => 'Ann', 'last_name' => 'Lee', 'phone' => '+61…'|null,
 *    'consent' => 'subscribed' | 'unsubscribed' | 'none' | null, 'tags' => ['vip', …]]
 *
 * consent is what the platform says about marketing permission: 'subscribed' (agreed), 'unsubscribed' (opted out),
 * 'none' (never agreed) or null (the platform has no such information).
 */
abstract class Provider
{
    use OAuthFlow;

    /** Short stable key: also stored as the person's source. */
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    /** Where it belongs in the catalogue: store | website | crm | email | payments | social | messaging. */
    public function category(): string
    {
        return 'store';
    }

    /**
     * The connection form: [{key, label, type: text|password, placeholder, help, secret, required}].
     * `url` is the store or site address (stored as store_url); other fields with secret = true are credentials (stored encrypted);
     * the rest are options of the connection (for example a Mailchimp audience id), kept in its settings under `config`.
     */
    abstract public function fields(): array;

    /** Does the person type an address? Platforms with one fixed API address (HubSpot, Mailchimp…) do not. */
    public function usesUrl(): bool
    {
        return collect($this->fields())->contains('key', 'url');
    }

    /** The address requests go to. Stores use what was typed; the others know their own. */
    public function baseUrl(Integration $integration): string
    {
        return $integration->store_url;
    }

    /** What makes two connections the same one (so the same store or account cannot be connected twice). Different stores or accounts differ. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($integration->store_url);
    }

    /** One option of the connection (a non-secret form field). */
    protected function option(Integration $integration, string $key, string $default = ''): string
    {
        return (string) (($integration->setting('config') ?? [])[$key] ?? $default);
    }

    /** Can the platform list the places contacts live in (Mailchimp audiences), so the person picks from a list instead of typing an id? */
    public function hasAudiences(): bool
    {
        return false;
    }

    /** @return array<int, array{id: string, name: string, members: int|null}> */
    public function audienceChoices(Integration $integration): array
    {
        return [];
    }

    /** Can People be sent to this platform too (not only read from it)? */
    public function supportsExport(): bool
    {
        return false;
    }

    /**
     * Sends a batch of People to the platform. Only people who are subscribed here are sent as subscribed; nobody who unsubscribed on the
     * platform is ever re-subscribed. Returns ['sent' => n, 'failed' => n, 'skipped' => n, 'error' => first failure message|null,
     * 'results' => [person id => ['status' => 'sent'|'failed'|'skipped', 'external_id' => the id the platform gave them|null, 'message' => string|null]]].
     */
    public function export(Integration $integration, iterable $people): array
    {
        throw new IntegrationException($this->label().' cannot receive people.');
    }

    /** How to get those details from the platform, as short steps. */
    abstract public function steps(): array;

    /** What this platform tells us about marketing consent, for the person choosing who to subscribe. */
    abstract public function consentNote(): string;

    /** Checks the connection and returns the store's name. Throws IntegrationException with a clear message. */
    abstract public function test(Integration $integration): string;

    /** One page of customers: [customers[], next cursor or null]. $since limits it to customers changed since then, where the platform can. */
    abstract public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since): array;

    /** Turns what a person typed (maybe "my-shop", maybe a full admin link) into the clean address that is stored. */
    public function normalizeUrl(string $input): string
    {
        $url = trim($input);
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            throw new IntegrationException('That is not a valid address.');
        }
        $path = preg_replace('~/(wp-admin|wp-json|rest|admin|index\.php)(/.*)?$~i', '', rtrim($parts['path'] ?? '', '/'));

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '').$path;
    }

    /** Settings this provider adds to the import options: [{key, label, type, options, default, help}]. */
    public function extraSettings(): array
    {
        return [];
    }

    public function describe(): array
    {
        return [
            'key' => $this->key(), 'label' => $this->label(), 'category' => $this->category(), 'uses_url' => $this->usesUrl(), 'description' => $this->description(),
            'fields' => $this->fields(), 'steps' => $this->steps(), 'consent_note' => $this->consentNote(), 'settings' => $this->extraSettings(), 'auto_connect' => $this->describeAutoConnect(), 'supports_export' => $this->supportsExport(), 'has_audiences' => $this->hasAudiences(),
        ];
    }

    // ------------------------------------------------------------------ shared plumbing

    protected function secret(Integration $i, string $key): string
    {
        return (string) (($i->secrets ?? [])[$key] ?? '');
    }

    /** A request to the platform. Refuses private addresses and (outside development) plain http, since credentials travel with it. */
    protected function send(Integration $integration, string $method, string $url, array $options = []): Response
    {
        try {
            UrlGuard::assertPublic($url);
        } catch (InvalidArgumentException $e) {
            throw new IntegrationException($e->getMessage());
        }
        if (str_starts_with(strtolower($url), 'http://') && ! config('services.outbound.allow_private')) {
            throw new IntegrationException('Use an https:// address. Credentials are only sent over a secure connection.');
        }

        $attempts = 0;
        $renewed = false;
        $options = $this->refreshed($integration, $options, false); // an OAuth token that has expired is renewed first
        while (true) {
            $attempts++;
            try {
                $request = Http::timeout(30)->acceptJson()->withoutRedirecting()->withUserAgent('FranchiseCareMarketing-Integration/1.0');
                if (! empty($options['headers'])) {
                    $request = $request->withHeaders($options['headers']);
                }
                if (! empty($options['basic'])) {
                    $request = $request->withBasicAuth($options['basic'][0], $options['basic'][1]);
                }
                $verb = strtoupper($method);
                $response = in_array($verb, ["POST", "PUT", "PATCH"], true) ? $request->send($verb, $url, ['json' => $options['json'] ?? []]) : (empty($options["query"]) ? $request->get($url) : $request->get($url, $options["query"]));
            } catch (ConnectionException $e) {
                throw new IntegrationException('Could not reach '.(parse_url($url, PHP_URL_HOST) ?: 'the site').'. Check the address and that the site is online.');
            }

            if ($response->status() === 401 && ! $renewed && $this->canRefresh($integration)) { // the token was revoked or expired early: renew once and retry
                $renewed = true;
                $options = $this->refreshed($integration, $options, true);

                continue;
            }
            if ($response->status() === 429 && $attempts < 3) {
                sleep(min(5, max(1, (int) $response->header('Retry-After'))));

                continue;
            }
            if ($response->redirect()) {
                $to = $response->header('Location');
                throw new IntegrationException('The address redirects'.($to ? ' to '.$to : '').'. Use the address it redirects to.');
            }

            return $response;
        }
    }

    /** The error to show for a failed answer, in the platform's own words where it gives them. */
    protected function failed(Response $response, string $denied, string $notFound): IntegrationException
    {
        return new IntegrationException(match (true) {
            in_array($response->status(), [401, 403], true) => $denied,
            $response->status() === 404 => $notFound,
            $response->status() === 429 => 'The platform is limiting requests right now. Try again in a few minutes.',
            $response->serverError() => 'The platform had a problem (HTTP '.$response->status().'). Try again in a few minutes.',
            default => 'The platform answered with an unexpected error (HTTP '.$response->status().').',
        });
    }

    /** The common shape of one customer. */
    protected function customer(string|int $id, ?string $email, ?string $first, ?string $last, ?string $phone, ?string $consent, array $tags = []): array
    {
        $clean = fn (?string $v) => ($v = trim((string) $v)) === '' ? null : $v;

        return [
            'id' => (string) $id, 'email' => $clean($email) ? mb_strtolower($clean($email)) : null,
            'first_name' => $clean($first), 'last_name' => $clean($last), 'phone' => $clean($phone), 'consent' => $consent, 'tags' => array_values(array_filter($tags)),
        ];
    }
}
