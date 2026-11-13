<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Person;

/** WhatsApp Business (Cloud API): one phone number of a WhatsApp Business account. */
class WhatsAppProvider extends MessagingProvider
{
    public function key(): string
    {
        return 'whatsapp';
    }

    public function channel(): string
    {
        return 'whatsapp';
    }

    public function label(): string
    {
        return 'WhatsApp Business';
    }

    public function description(): string
    {
        return 'Send campaigns to people’s WhatsApp, using their phone number. Each connection is one WhatsApp Business phone number.';
    }

    public function maxLength(): int
    {
        return 4096;
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => 'EAA…', 'help' => 'A permanent access token of a system user with whatsapp_business_messaging permission.', 'secret' => true],
            ['key' => 'phone_number_id', 'label' => 'Phone number ID', 'type' => 'text', 'placeholder' => '109876543210987', 'help' => 'WhatsApp Manager → API setup → Phone number ID (not the phone number itself).', 'secret' => false, 'required' => true],
            ['key' => 'language', 'label' => 'Template language', 'type' => 'text', 'placeholder' => 'en_US', 'help' => 'The language code of your approved message template. Default en_US.', 'secret' => false, 'required' => false],
        ];
    }

    public function steps(): array
    {
        return [
            'In Meta for Developers create a Business app and add the WhatsApp product, with a WhatsApp Business account and a phone number.',
            'In Business Settings → System users create a system user, give it the WhatsApp account, and generate a token with whatsapp_business_messaging and whatsapp_business_management.',
            'In WhatsApp Manager → API setup copy the Phone number ID.',
            'Create and get approved a message template with one variable ({{1}}), for example “{{1}}”. A campaign puts its text in that variable.',
            'Paste the token and the Phone number ID here.',
        ];
    }

    protected function rule(): string
    {
        return 'WhatsApp only lets you start a conversation with an approved message template, and people must have agreed to receive messages from you. Enter the template name in the campaign. Without a template, only people who wrote to you in the last 24 hours receive the text.';
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
        return 'WhatsApp refused the token. Use a system-user token with the whatsapp_business_messaging permission.';
    }

    public function identity(Integration $integration): string
    {
        return 'whatsapp:'.$this->phoneId($integration);
    }

    protected function phoneId(Integration $i): string
    {
        $id = trim($this->option($i, 'phone_number_id'));
        if (! preg_match('/^[0-9]{5,30}$/', $id)) {
            throw new IntegrationException('The phone number ID is only digits. Copy it from WhatsApp Manager → API setup.');
        }

        return $id;
    }

    public function test(Integration $integration): string
    {
        $n = $this->get($integration, '/'.$this->phoneId($integration), ['fields' => 'display_phone_number,verified_name']);

        return trim(($n['verified_name'] ?? 'WhatsApp').' '.($n['display_phone_number'] ?? ''));
    }

    /** WhatsApp wants the number as digits with the country code. */
    public function address(Person $person): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $person->phone);

        return strlen($d) >= 8 ? $d : null;
    }

    public function deliver(Integration $integration, string $to, string $text, array $options = []): string
    {
        $template = trim((string) ($options['template'] ?? ''));
        $payload = ['messaging_product' => 'whatsapp', 'to' => preg_replace('/\D+/', '', $to)] + ($template !== ''
            ? ['type' => 'template', 'template' => ['name' => $template, 'language' => ['code' => $this->option($integration, 'language', 'en_US') ?: 'en_US'], 'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => preg_replace('/\s*[\r\n\t]+\s*/', ' ', $text)]]]]]]
            : ['type' => 'text', 'text' => ['body' => $text]]);

        $r = $this->send($integration, 'POST', $this->api($integration).'/'.$this->phoneId($integration).'/messages', ['headers' => $this->headers($integration), 'json' => $payload]);
        if (! $r->successful()) {
            throw $this->reject($r);
        }

        return (string) ($r->json('messages.0.id') ?? '');
    }
}
