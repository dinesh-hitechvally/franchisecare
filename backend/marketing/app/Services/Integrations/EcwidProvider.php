<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Ecwid (Lightspeed E-Series) customers, through the store id and a secret token with the “Read customers” permission. */
class EcwidProvider extends ApiProvider
{
    public function key(): string
    {
        return 'ecwid';
    }

    public function label(): string
    {
        return 'Ecwid';
    }

    public function description(): string
    {
        return 'Import the customers of your Ecwid store, with their marketing choice.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'store_id', 'label' => 'Store ID', 'type' => 'text', 'placeholder' => '12345678', 'help' => 'Shown in your Ecwid control panel under Settings → General → Store profile.', 'secret' => false],
            ['key' => 'secret_token', 'label' => 'Secret token', 'type' => 'password', 'placeholder' => 'secret_…', 'help' => 'From your Ecwid app (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the Ecwid control panel open Apps → Custom apps (or Develop apps) and create a custom app. Give it the “Read customers” permission.',
            'Open the app and copy its Secret token (it starts with secret_).',
            'Your Store ID is on Settings → General → Store profile.',
            'Paste both here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Ecwid records whether a customer accepted marketing at checkout. Those customers are subscribed here; the others are imported but not subscribed. Choose “Everyone” only if you have permission.';
    }

    /** The same store is the same connection, whichever key it is reached with. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($this->option($integration, 'store_id'));
    }

    protected function api(Integration $i): string
    {
        return 'https://app.ecwid.com/api/v3/'.$this->slug($i, 'store_id', 'store ID');
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'secret_token')];
    }

    protected function refused(): string
    {
        return 'Ecwid refused the token. Check the store ID and the secret token, and that the app can read customers.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['limit' => 1]);
        try {
            $profile = $this->get($integration, '/profile');
        } catch (IntegrationException) {
            $profile = [];
        }

        return (string) ($profile['company']['companyName'] ?? $profile['generalInfo']['storeUrl'] ?? 'Ecwid store');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = max(0, (int) $cursor);
        $body = $this->get($integration, '/customers', array_filter(['limit' => $limit, 'offset' => $offset, 'updatedFrom' => $since?->getTimestamp()], fn ($v) => $v !== null));

        $customers = [];
        foreach ($body['items'] ?? [] as $c) {
            $p = $c['billingPerson'] ?? [];
            [$first, $last] = isset($p['firstName']) || isset($p['lastName']) ? [$p['firstName'] ?? null, $p['lastName'] ?? null] : $this->split($p['name'] ?? ($c['name'] ?? null));
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $first, $last, $p['phone'] ?? null, ! empty($c['acceptMarketing']) ? 'subscribed' : 'none');
        }
        $total = (int) ($body['total'] ?? 0);

        return [$customers, $offset + $limit < $total ? (string) ($offset + $limit) : null];
    }
}
