<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Ghost members (newsletter subscribers and paying members), through a custom integration's Admin API key. */
class GhostProvider extends ApiProvider
{
    public function key(): string
    {
        return 'ghost';
    }

    public function label(): string
    {
        return 'Ghost';
    }

    public function category(): string
    {
        return 'website';
    }

    public function description(): string
    {
        return 'Import the members of your Ghost site, with whether they subscribed to your newsletter.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Site address', 'type' => 'text', 'placeholder' => 'https://your-site.com', 'help' => 'The address of your Ghost site, over https.', 'secret' => false],
            ['key' => 'admin_key', 'label' => 'Admin API key', 'type' => 'password', 'placeholder' => 'id:secret', 'help' => 'From Settings → Integrations (see the steps). It looks like 24 letters/numbers, a colon, then 64 more.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Ghost Admin open Settings → Advanced → Integrations and choose Add custom integration.',
            'Name it (for example “FranchiseCare Marketing”) and create it.',
            'Copy the Admin API key (the long value with a colon in the middle, not the Content API key).',
            'Paste your site address and the Admin API key here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Ghost records whether each member subscribed to the newsletter. Subscribed members are subscribed here; members who turned it off are unsubscribed. Nothing else is switched on.';
    }

    /** Ghost wants a short-lived token signed with the key. */
    protected function headers(Integration $i): array
    {
        [$id, $secret] = array_pad(explode(':', $this->secret($i, 'admin_key'), 2), 2, '');
        if ($id === '' || ! ctype_xdigit($secret) || strlen($secret) % 2) {
            throw new IntegrationException('That does not look like a Ghost Admin API key. It is two parts joined by a colon (id:secret).');
        }
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $head = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT', 'kid' => $id]));
        $body = $b64(json_encode(['iat' => time(), 'exp' => time() + 300, 'aud' => '/admin/']));
        $sign = $b64(hash_hmac('sha256', "$head.$body", hex2bin($secret), true));

        return ['Authorization' => "Ghost $head.$body.$sign", 'Accept-Version' => 'v5.0'];
    }

    protected function refused(): string
    {
        return 'Ghost refused the key. Use the Admin API key of a custom integration on this site.';
    }

    public function test(Integration $integration): string
    {
        $site = $this->get($integration, '/ghost/api/admin/site/');

        return (string) ($site['site']['title'] ?? 'Ghost site');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $page = max(1, (int) $cursor);
        $query = ['limit' => $limit, 'page' => $page, 'order' => 'created_at asc'];
        if ($since) {
            $query['filter'] = "updated_at:>'".$this->stamp($since, 'Y-m-d H:i:s')."'";
        }
        $body = $this->get($integration, '/ghost/api/admin/members/', $query);

        $customers = [];
        foreach ($body['members'] ?? [] as $m) {
            [$first, $last] = $this->split($m['name'] ?? null);
            $customers[] = $this->customer($m['id'] ?? '', $m['email'] ?? null, $first, $last, null, array_key_exists('subscribed', $m) ? ($m['subscribed'] ? 'subscribed' : 'unsubscribed') : null);
        }
        $next = $body['meta']['pagination']['next'] ?? null;

        return [$customers, $next ? (string) $next : null];
    }
}
