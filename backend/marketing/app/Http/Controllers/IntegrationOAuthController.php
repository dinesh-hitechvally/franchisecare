<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\Integrations\IntegrationException;
use App\Services\Integrations\Provider;
use App\Services\Integrations\Providers;
use App\Services\Integrations\Syncs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Connect with …" for Integrations: the person approves access on the platform's own page and the app receives the keys itself,
 * so nothing is pasted. Three kinds of approval: the usual OAuth sign-in, WooCommerce's key approval page and WordPress's
 * application-password approval page. What the person chose (list, tags, permission…) waits in the cache under a one-time
 * state while they are on the platform's page.
 */
class IntegrationOAuthController extends Controller
{
    protected const TTL_MINUTES = 15;

    protected function cacheKey(string $state): string
    {
        return 'integration_oauth:'.$state;
    }

    protected function callbackUrl(string $path = 'callback'): string
    {
        return Provider::callbackUrl($path);
    }

    protected function back(): string
    {
        return rtrim((string) config('services.mail_oauth.frontend_url'), '/').'/integrations';
    }

    /** Step 1 (signed-in user): where to send the browser to approve access. */
    public function start(Request $request, IntegrationController $integrations)
    {
        $provider = Providers::find($request->input('provider'));
        abort_unless($provider, 422, 'Choose a platform to connect.');
        $spec = $provider->autoConnect();
        abort_unless($spec, 422, $provider->label().' is connected by pasting its details.');
        abort_unless($provider->autoConnectReady(), 422, match ($spec['mode']) {
            'oauth' => 'One-click connect is not set up for '.$provider->label().' yet. Ask the platform administrator to add its OAuth client id and secret, or paste the details instead.',
            default => 'One-click connect needs this app to be reachable from the internet over https. Paste the details instead.',
        });

        $data = $request->validate($integrations->rules($provider, false));
        $input = [];
        if (in_array('url', $spec['asks'] ?? [], true)) {
            abort_unless(filled($data['url'] ?? null), 422, 'Enter the address of your '.$provider->label().' site or store.');
            try {
                $input['url'] = $provider->normalizeUrl($data['url']);
            } catch (IntegrationException $e) {
                abort(422, $e->getMessage());
            }
        }

        $state = Str::random(48);
        $verifier = ! empty($spec['pkce']) ? Str::random(64) : null;
        Cache::put($this->cacheKey($state), [
            'company' => $request->user()->company_id, 'provider' => $provider->key(), 'input' => $input, 'settings' => $integrations->settings($provider, $data),
            'name' => $data['name'] ?? null, 'import_now' => $request->boolean('import_now', true), 'verifier' => $verifier, 'redirect' => $this->callbackUrl(),
        ], now()->addMinutes(self::TTL_MINUTES));

        return ['url' => $this->approvalUrl($provider, $spec['mode'], $input, $state, $verifier)];
    }

    protected function approvalUrl(Provider $provider, string $mode, array $input, string $state, ?string $verifier): string
    {
        $back = $this->back();

        return match ($mode) {
            'oauth' => $provider->authorizeUrl($input, $state, $this->callbackUrl(), $verifier ? rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=') : null),
            // WooCommerce shows its own "approve" page and then posts the new keys to callback_url, so they never pass through the browser
            'woocommerce' => $input['url'].'/wc-auth/v1/authorize?'.http_build_query([
                'app_name' => 'FranchiseCare Marketing', 'scope' => 'read', 'user_id' => $state, 'return_url' => $back.'?woocommerce=1', 'callback_url' => $this->callbackUrl('woocommerce'),
            ], '', '&', PHP_QUERY_RFC3986),
            // WordPress shows its own "approve" page and then returns the browser with a new application password
            'wordpress' => $input['url'].'/wp-admin/authorize-application.php?'.http_build_query([
                'app_name' => 'FranchiseCare Marketing', 'app_id' => '6f1c1d2e-4c1a-4b8e-9d52-5a0f2f0a7b11',
                'success_url' => $this->callbackUrl().'?state='.$state, 'reject_url' => $back.'?oauth_error='.urlencode('Access was not approved.'),
            ], '', '&', PHP_QUERY_RFC3986),
        };
    }

    /** Step 2 (the platform sends the browser back here): turn the approval into a connection and return to the Integrations page. */
    public function callback(Request $request)
    {
        $back = $this->back();
        $p = Cache::pull($this->cacheKey((string) $request->query('state')));
        if (! $p) {
            return redirect($back.'?oauth_error='.urlencode('The sign-in link expired. Please try again.'));
        }
        $provider = Providers::find($p['provider']);
        if (! $provider || $request->query('error') || ($provider->autoConnect()['mode'] === 'oauth' && ! $request->query('code'))) {
            return redirect($back.'?oauth_error='.urlencode('Access was not approved.'));
        }

        try {
            if ($provider->autoConnect()['mode'] === 'wordpress') {
                [$integration, $again] = $this->wordpress($request, $p, $provider);
            } else {
                [$integration, $again] = $this->oauth($request, $p, $provider);
            }
        } catch (IntegrationException $e) {
            return redirect($back.'?oauth_error='.urlencode($e->getMessage()));
        } catch (Throwable $e) {
            report($e);

            return redirect($back.'?oauth_error='.urlencode('Could not finish connecting '.$provider->label().'. Please try again.'));
        }

        return redirect($back.'?'.($again ? 'reconnected=' : 'connected=').urlencode($integration->name));
    }

    protected function oauth(Request $request, array $p, Provider $provider): array
    {
        if (! $provider->verifyCallback($request->query())) {
            throw new IntegrationException('That sign-in did not come from '.$provider->label().'. Please try again.');
        }
        if ($provider->key() === 'shopify' && isset($request->query()['shop'])) {
            try {
                $same = $provider->normalizeUrl((string) $request->query('shop')) === ($p['input']['url'] ?? null);
            } catch (IntegrationException) {
                $same = false;
            }
            if (! $same) {
                throw new IntegrationException('That sign-in was for a different Shopify store. Please try again.');
            }
        }
        $tokens = $provider->exchange($p['input'], (string) $request->query('code'), $p['redirect'], $p['verifier'] ?? null);

        return $this->finish($p, $provider, fn (Integration $i) => $provider->oauthConnect($i, $tokens, $p['input']), $this->granted($tokens));
    }

    /** The permissions the platform says it granted (its token answer carries them as a list, separated by spaces or commas), or null when it does not say. */
    protected function granted(array $tokens): ?array
    {
        $scope = $tokens['raw']['scope'] ?? $tokens['raw']['scopes'] ?? null;
        $list = is_array($scope) ? $scope : preg_split('/[\s,]+/', (string) $scope);
        $list = array_values(array_unique(array_filter(array_map('trim', $list), fn ($s) => is_string($s) && $s !== '')));
        sort($list);

        return $list ?: null;
    }

    /** WordPress returns the browser with the site address, the user and a new application password. */
    protected function wordpress(Request $request, array $p, Provider $provider): array
    {
        $login = (string) $request->query('user_login');
        $password = (string) $request->query('password');
        if ($login === '' || $password === '') {
            throw new IntegrationException('Access was not approved.');
        }

        return $this->finish($p, $provider, function (Integration $i) use ($p, $login, $password) {
            $i->store_url = $p['input']['url'];
            $i->secrets = ['username' => $login, 'app_password' => $password];
        });
    }

    /** WooCommerce posts the new keys here itself (server to server). The state travels as its user_id. */
    public function woocommerce(Request $request)
    {
        $p = Cache::pull($this->cacheKey((string) $request->input('user_id')));
        $provider = Providers::find('woocommerce');
        $key = (string) $request->input('consumer_key');
        $secret = (string) $request->input('consumer_secret');
        abort_unless($p && ($p['provider'] ?? null) === 'woocommerce' && $key !== '' && $secret !== '', 400, 'Unknown or expired approval.');

        try {
            $this->finish($p, $provider, function (Integration $i) use ($p, $key, $secret) {
                $i->store_url = $p['input']['url'];
                $i->secrets = ['consumer_key' => $key, 'consumer_secret' => $secret];
            });
        } catch (IntegrationException $e) {
            report($e);
            abort(422, $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Saves the connection (no signed-in user here: the company comes from the state). Connecting a store or account that is already
     * connected renews that connection's access instead of adding a copy.
     *
     * @return array{0: Integration, 1: bool} the connection, and whether it was an existing one renewed
     */
    protected function finish(array $p, Provider $provider, callable $apply, ?array $granted = null): array
    {
        $integration = new Integration(['provider' => $provider->key(), 'settings' => $p['settings']]);
        $integration->forceFill(['company_id' => $p['company']]);
        $apply($integration);
        if (! $provider->usesUrl() && ! $integration->store_url) {
            $integration->store_url = $provider->baseUrl($integration);
        }

        $identity = $provider->identity($integration);
        $account = ($integration->secrets ?? [])['account'] ?? null;
        $existing = Integration::withoutGlobalScope('company')->where('company_id', $p['company'])->where('provider', $provider->key())->get()
            ->first(fn (Integration $o) => $provider->identity($o) === $identity || ($account && (($o->secrets ?? [])['account'] ?? null) === $account));

        $detected = $provider->test($integration); // the platform confirms the new access works before anything is saved

        if ($existing) {
            $existing->forceFill([
                'secrets' => $integration->secrets, 'store_url' => $integration->store_url ?: $existing->store_url, 'status' => 'connected', 'last_error' => null,
                'settings' => $granted ? array_merge($existing->settings ?? [], ['granted_scopes' => $granted]) : $existing->settings,
            ])->save();

            return [$existing, true];
        }

        $integration->forceFill([
            'name' => trim((string) ($p['name'] ?? '')) ?: $detected, 'status' => 'connected', 'last_error' => null,
            'settings' => $granted ? array_merge($integration->settings ?? [], ['granted_scopes' => $granted]) : $integration->settings, // what the platform actually allowed, shown in Edit Configuration
        ])->save();
        if (($p['import_now'] ?? true) && ! ($provider instanceof \App\Services\Integrations\MessagingProvider)) {
            Syncs::start($integration, 'connect', true);
        }

        return [$integration, false];
    }
}
