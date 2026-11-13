<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** People who filled in a LinkedIn Lead Gen Form of one ad account, read with the Lead Sync API (leadFormResponses). */
class LinkedInLeadsProvider extends ApiProvider
{
    /** The LinkedIn API version sent with every call (YYYYMM). LinkedIn retires versions after about a year: raise this when it does. */
    private const VERSION = '202610';

    public function key(): string
    {
        return 'linkedin';
    }

    public function label(): string
    {
        return 'LinkedIn Lead Gen Forms';
    }

    public function category(): string
    {
        return 'social';
    }

    public function description(): string
    {
        return 'People who filled in the Lead Gen Forms of a LinkedIn ad account: name, email, phone. Each connection reads one ad account.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => 'AQV…', 'help' => 'A member access token with the r_marketing_leadgen_automation permission (needs Lead Sync API access).', 'secret' => true],
            ['key' => 'account_id', 'label' => 'Ad account ID', 'type' => 'text', 'placeholder' => '512345678', 'help' => 'The number in the Campaign Manager address (…/accounts/512345678/…).', 'secret' => false, 'required' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the LinkedIn Developer Portal create an app and request the Advertising API product, with Lead Sync API access.',
            'Generate a member access token that includes r_marketing_leadgen_automation (the member needs a role such as Lead Gen Forms Manager on the ad account).',
            'In Campaign Manager copy the ad account ID from the address.',
            'Paste the token and the ad account ID here. LinkedIn tokens last about 60 days, so reconnect when it expires.',
        ];
    }

    public function consentNote(): string
    {
        return 'LinkedIn lead forms carry their own consent checkboxes that are not mapped to a subscription here, so leads are added without a subscription unless you choose to subscribe them in the import options.';
    }

    protected function api(Integration $i): string
    {
        return 'https://api.linkedin.com/rest';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token'), 'LinkedIn-Version' => self::VERSION, 'X-Restli-Protocol-Version' => '2.0.0'];
    }

    protected function refused(): string
    {
        return 'LinkedIn refused the token. It needs the r_marketing_leadgen_automation permission, the member needs access to the ad account, and the token may have expired.';
    }

    public function identity(Integration $integration): string
    {
        return 'linkedin:'.$this->accountId($integration);
    }

    protected function accountId(Integration $i): string
    {
        $id = trim($this->option($i, 'account_id'));
        if (! preg_match('/^[0-9]{3,20}$/', $id)) {
            throw new IntegrationException('The ad account ID is only digits. Copy it from the Campaign Manager address.');
        }

        return $id;
    }

    /** The owner of the leads, in the Rest.li form LinkedIn expects: the structure as it is, the URN encoded. */
    protected function owner(Integration $i): string
    {
        return '(sponsoredAccount:urn%3Ali%3AsponsoredAccount%3A'.$this->accountId($i).')';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/leadFormResponses?q=owner&owner='.$this->owner($integration).'&leadType=(leadType:SPONSORED)&count=1');

        return 'Ad account '.$this->accountId($integration);
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $start = max(0, (int) $cursor);
        $path = '/leadFormResponses?q=owner&owner='.$this->owner($integration).'&leadType=(leadType:SPONSORED)&count='.$limit.'&start='.$start;
        if ($since) {
            $path .= '&submittedAtTimeRange=(start:'.($since->getTimestamp() * 1000).')';
        }
        $body = $this->get($integration, $path);

        $forms = []; // form id => [questionId => predefined field]
        $customers = [];
        foreach ($body['elements'] ?? [] as $lead) {
            $form = (string) ($lead['versionedLeadGenFormUrn'] ?? '');
            if (! isset($forms[$form])) {
                $forms[$form] = $this->questions($integration, $form);
            }
            $a = [];
            foreach ($lead['formResponse']['answers'] ?? [] as $answer) {
                $field = $forms[$form][$answer['questionId'] ?? 0] ?? null;
                if ($field) {
                    $a[$field] = $answer['answerDetails']['textQuestionAnswer']['answer'] ?? null;
                }
            }
            $customers[] = $this->customer($lead['id'] ?? '', $a['EMAIL'] ?? null, $a['FIRST_NAME'] ?? null, $a['LAST_NAME'] ?? null, $a['PHONE_NUMBER'] ?? null, 'none', ['LinkedIn lead']);
        }

        $next = $start + $limit;

        return [$customers, $next < (int) ($body['paging']['total'] ?? 0) ? (string) $next : null];
    }

    /** questionId => predefined field (FIRST_NAME, EMAIL…) of the form a lead was sent through. */
    protected function questions(Integration $i, string $versionedUrn): array
    {
        if (! preg_match('/leadGenForm:(\d+)/', $versionedUrn, $m)) {
            return [];
        }
        try {
            $form = $this->get($i, '/leadForms/'.$m[1]);
        } catch (IntegrationException) {
            return []; // a form that cannot be read gives leads without details, which are skipped by the importer
        }
        $map = [];
        foreach ($form['content']['questions'] ?? [] as $q) {
            if (! empty($q['predefinedField'])) {
                $map[$q['questionId']] = $q['predefinedField'];
            }
        }

        return $map;
    }
}
