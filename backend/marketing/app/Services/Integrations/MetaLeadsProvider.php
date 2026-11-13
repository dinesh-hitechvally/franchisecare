<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/**
 * People who filled in a Meta lead form (Instant Form), read from the Graph API with a page access token.
 * Facebook and Instagram ads use the same forms, so both providers share this; each connection reads one form.
 */
abstract class MetaLeadsProvider extends ApiProvider
{
    public function category(): string
    {
        return 'social';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Page access token', 'type' => 'password', 'placeholder' => 'EAA…', 'help' => 'A long-lived access token of the Page that owns the form, with leads_retrieval permission.', 'secret' => true],
            ['key' => 'form_id', 'label' => 'Lead form ID', 'type' => 'text', 'placeholder' => '1234567890123456', 'help' => 'Shown in Meta Business Suite → Instant forms. One connection reads one form; connect more forms separately.', 'secret' => false, 'required' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Meta for Developers create an app (type Business) and add the Marketing API / Leads Access permissions.',
            'In the Graph API Explorer generate a Page access token with pages_show_list, pages_manage_ads and leads_retrieval, then extend it to a long-lived token.',
            'In Meta Business Suite → Instant forms, open the form and copy its ID.',
            'Paste the token and the form ID here.',
        ];
    }

    public function consentNote(): string
    {
        return 'A lead form does not say whether the person agreed to marketing email, so leads are added without a subscription unless you choose to subscribe them in the import options.';
    }

    protected function api(Integration $i): string
    {
        return 'https://graph.facebook.com/v21.0';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')];
    }

    protected function refused(): string
    {
        return 'Meta refused the token. Use a Page access token with the leads_retrieval permission, and check that it has not expired.';
    }

    /** What makes two connections the same one: the form. */
    public function identity(Integration $integration): string
    {
        return $this->key().':'.$this->formId($integration);
    }

    protected function formId(Integration $i): string
    {
        $id = trim($this->option($i, 'form_id'));
        if (! preg_match('/^[0-9]{5,30}$/', $id)) {
            throw new IntegrationException('The lead form ID is only digits. Copy it from Meta Business Suite → Instant forms.');
        }

        return $id;
    }

    public function test(Integration $integration): string
    {
        $form = $this->get($integration, '/'.$this->formId($integration), ['fields' => 'name,status']);

        return (string) ($form['name'] ?? 'Lead form');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $query = array_filter(['fields' => 'id,created_time,field_data', 'limit' => $limit, 'after' => $cursor]);
        if ($since) {
            $query['filtering'] = json_encode([['field' => 'time_created', 'operator' => 'GREATER_THAN', 'value' => $since->getTimestamp()]]);
        }
        $body = $this->get($integration, '/'.$this->formId($integration).'/leads', $query);

        $customers = [];
        foreach ($body['data'] ?? [] as $lead) {
            $a = [];
            foreach ($lead['field_data'] ?? [] as $f) {
                $a[$f['name'] ?? ''] = $f['values'][0] ?? null;
            }
            [$first, $last] = isset($a['first_name']) || isset($a['last_name']) ? [$a['first_name'] ?? null, $a['last_name'] ?? null] : $this->split($a['full_name'] ?? null);
            $customers[] = $this->customer($lead['id'] ?? '', $a['email'] ?? null, $first, $last, $a['phone_number'] ?? null, 'none', [$this->label().' lead']);
        }

        return [$customers, isset($body['paging']['next']) ? ($body['paging']['cursors']['after'] ?? null) : null];
    }
}
