<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;

/**
 * An online marketplace (Etsy, eBay, Amazon…): the buyers of the shop's orders are imported as People.
 * A marketplace shares a buyer's details only to fulfil the order, so what comes back depends on the platform: some give an email, some only a
 * phone number or a masked address. Nobody is subscribed unless the person chooses "Everyone" (see consentNote).
 */
abstract class MarketplaceProvider extends ApiProvider
{
    public function category(): string
    {
        return 'marketplace';
    }

    public function consentNote(): string
    {
        return $this->label().' shares a buyer’s details to fulfil the order, and its rules usually do not allow marketing to buyers who did not agree. It does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if the buyers gave you permission and '.$this->label().'’s terms allow it.';
    }

    /**
     * An access token made from the credentials the person pasted, kept until shortly before it expires. If the platform answers with a new
     * refresh token (Mercado Libre's can be used only once) the new one replaces the stored one.
     *
     * @param  array  $form  the token request (grant_type …), sent as a form
     * @param  array  $options  extra options of the request: basic (client id and secret) or headers
     */
    protected function minted(Integration $i, string $url, array $form, array $options = []): string
    {
        $key = 'marketplace_token:'.$this->key().':'.sha1(json_encode($i->secrets ?? []).'|'.json_encode($form));
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $response = $this->send($i, 'POST', $url, ['form' => $form] + $options);
        $body = $response->json() ?? [];
        if (! $response->successful() || empty($body['access_token'])) {
            $why = $body['error_description'] ?? $body['message'] ?? (is_string($body['error'] ?? null) ? $body['error'] : null);

            throw new IntegrationException($this->label().' did not accept the credentials'.($why ? ' ('.mb_substr((string) $why, 0, 120).')' : '').'. Check them and try again.');
        }

        if (isset($form['refresh_token']) && ! empty($body['refresh_token']) && $body['refresh_token'] !== $form['refresh_token']) {
            $secrets = $i->secrets ?? [];
            $secrets['refresh_token'] = $body['refresh_token'];
            $i->secrets = $secrets;
            if ($i->exists) {
                $i->save();
            }
        }
        Cache::put($key, $body['access_token'], max(60, (int) ($body['expires_in'] ?? 900) - 120));

        return (string) $body['access_token'];
    }

    /** The access token to send: the one a one-click connection keeps up to date, or one made from the pasted credentials. */
    abstract protected function token(Integration $i): string;
}
