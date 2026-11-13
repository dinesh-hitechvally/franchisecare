<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;

/**
 * "Connect with …" instead of pasting keys. A provider opts in by returning a spec from autoConnect():
 *
 *   ['mode' => 'oauth', 'authorize' => url, 'token' => url, 'scopes' => [...], 'token_secret' => 'access_token',
 *    'client_auth' => 'body'|'basic', 'token_format' => 'form'|'json', 'pkce' => bool, 'asks' => ['url']]   // the usual OAuth 2 code flow
 *   ['mode' => 'woocommerce', 'asks' => ['url']]                                                          // WooCommerce's own key approval page
 *   ['mode' => 'wordpress', 'asks' => ['url']]                                                            // WordPress "application password" approval page
 *
 * The OAuth client id and secret of each platform are the platform owner's, set in .env (config services.integrations, entries with auth_type oauth).
 */
trait OAuthFlow
{
    /** null = this platform is connected by pasting details only. */
    public function autoConnect(): ?array
    {
        return null;
    }

    /** @return array{0: ?string, 1: ?string} the client id and secret registered with the platform */
    public function oauthCredentials(): array
    {
        return [config('services.integrations.'.$this->key().'.client_id'), config('services.integrations.'.$this->key().'.client_secret')];
    }

    /** Can the "connect" button be offered right now? */
    public function autoConnectReady(): bool
    {
        $spec = $this->autoConnect();

        return match ($spec['mode'] ?? null) {
            'oauth' => ! in_array(null, array_map(fn ($v) => $v ?: null, $this->oauthCredentials()), true),
            'woocommerce' => str_starts_with(strtolower((string) config('app.url')), 'https://') || config('services.outbound.allow_private'), // WooCommerce posts the keys back to us
            'wordpress' => true,
            default => false,
        };
    }

    public function describeAutoConnect(): ?array
    {
        $spec = $this->autoConnect();

        return $spec ? ['mode' => $spec['mode'], 'available' => $this->autoConnectReady(), 'asks' => $spec['asks'] ?? []] : null;
    }

    protected function oauthAuthorizeBase(array $input): string
    {
        return $this->autoConnect()['authorize'];
    }

    protected function oauthTokenUrl(array $input): string
    {
        return $this->autoConnect()['token'];
    }

    /** Where to send the browser to approve access. */
    public function authorizeUrl(array $input, string $state, string $redirect, ?string $challenge = null): string
    {
        $spec = $this->autoConnect();
        $query = ['client_id' => $this->oauthCredentials()[0], 'redirect_uri' => $redirect, 'response_type' => 'code', 'state' => $state]
            + (($spec['scopes'] ?? []) ? [($spec['scope_param'] ?? 'scope') => implode($spec['scope_separator'] ?? ' ', $spec['scopes'])] : [])
            + ($spec['authorize_params'] ?? []);
        if ($challenge) {
            $query += ['code_challenge' => $challenge, 'code_challenge_method' => 'S256'];
        }

        return $this->oauthAuthorizeBase($input).'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    protected function exchangeParams(string $code, string $redirect, ?string $verifier): array
    {
        return ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect] + ($verifier ? ['code_verifier' => $verifier] : []);
    }

    /** Trades the approval code for tokens. */
    public function exchange(array $input, string $code, string $redirect, ?string $verifier = null): array
    {
        return $this->tokenRequest($this->oauthTokenUrl($input), $this->exchangeParams($code, $redirect, $verifier));
    }

    /** A new access token from the refresh token. */
    public function refreshTokens(Integration $i): array
    {
        return $this->tokenRequest($this->oauthTokenUrl(['url' => $i->store_url]), ['grant_type' => 'refresh_token', 'refresh_token' => ($i->secrets ?? [])['refresh_token'] ?? '']);
    }

    protected function tokenRequest(string $url, array $params): array
    {
        $spec = $this->autoConnect();
        [$id, $secret] = $this->oauthCredentials();
        $request = Http::timeout(30)->acceptJson()->withUserAgent('FranchiseCareMarketing-Integration/1.0');
        $auth = $spec['client_auth'] ?? 'body';
        if ($auth === 'basic') {
            $request = $request->withBasicAuth((string) $id, (string) $secret);
        } elseif ($auth === 'bearer') {
            $request = $request->withToken((string) $secret); // Stripe: the platform's secret key
        } else {
            $params += ['client_id' => $id, 'client_secret' => $secret];
        }
        $response = ($spec['token_format'] ?? 'form') === 'json' ? $request->asJson()->post($url, $params) : $request->asForm()->post($url, $params);
        $body = $response->json() ?? [];

        if (! $response->successful() || empty($body['access_token'])) {
            $why = $body['error_description'] ?? (is_string($body['error'] ?? null) ? $body['error'] : null);
            throw new IntegrationException($this->label().' did not accept the sign-in'.($why ? ' ('.mb_substr((string) $why, 0, 120).')' : '').'. Please try again.');
        }

        return [
            'access_token' => (string) $body['access_token'], 'refresh_token' => $body['refresh_token'] ?? null,
            'expires_at' => isset($body['expires_in']) ? time() + (int) $body['expires_in'] : (isset($body['expires_at']) ? (strtotime((string) $body['expires_at']) ?: null) : null),
            'raw' => $body,
        ];
    }

    /** Checks the browser came back from the platform untampered (Shopify signs its callback). */
    public function verifyCallback(array $query): bool
    {
        return true;
    }

    /** Puts the tokens on the connection. A provider overrides this to also learn its address, account or data centre. */
    public function oauthConnect(Integration $integration, array $tokens, array $input): void
    {
        $spec = $this->autoConnect();
        $integration->secrets = array_filter([
            $spec['token_secret'] => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token'] ?? null, 'expires_at' => $tokens['expires_at'] ?? null,
            'oauth' => true, 'account' => $this->oauthAccount($tokens),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** An id of the connected account when the platform tells it, so connecting the same account again reconnects it instead of adding a copy. */
    protected function oauthAccount(array $tokens): ?string
    {
        return null;
    }

    protected function canRefresh(Integration $i): bool
    {
        $s = $i->secrets ?? [];

        return ! empty($s['oauth']) && ! empty($s['refresh_token']) && ($this->autoConnect()['mode'] ?? null) === 'oauth';
    }

    /** Swaps an expired (or, when forced, refused) access token for a fresh one in the request that is about to be sent. */
    protected function refreshed(Integration $i, array $options, bool $force): array
    {
        if (! $this->canRefresh($i)) {
            return $options;
        }
        $s = $i->secrets;
        if (! $force && ($s['expires_at'] ?? PHP_INT_MAX) > time() + 60) {
            return $options;
        }
        $key = $this->autoConnect()['token_secret'];
        $old = (string) ($s[$key] ?? '');

        try {
            $tokens = $this->refreshTokens($i);
        } catch (IntegrationException) {
            throw new IntegrationException('The connection to '.$this->label().' has expired. Open Settings and connect again.');
        }
        $s[$key] = $tokens['access_token'];
        $s['refresh_token'] = $tokens['refresh_token'] ?: ($s['refresh_token'] ?? null);
        $s['expires_at'] = $tokens['expires_at'];
        $i->secrets = array_filter($s, fn ($v) => $v !== null);
        if ($i->exists) {
            $i->save();
        }
        if ($old !== '') {
            array_walk_recursive($options, function (&$v) use ($old, $tokens) {
                $v = is_string($v) ? str_replace($old, $tokens['access_token'], $v) : $v;
            });
        }

        return $options;
    }
}
