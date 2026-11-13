<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Person;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;

/**
 * A connected page or account that campaigns can send messages through (WhatsApp, Messenger, X).
 * Nothing is imported from it: it only appears in Connections, and as a channel when a campaign is made.
 */
abstract class MessagingProvider extends ApiProvider
{
    /** The campaign type this connection sends: whatsapp | messenger | x. */
    abstract public function channel(): string;

    /** Longest message the platform accepts. */
    abstract public function maxLength(): int;

    /** What the connected account is called on the platform. */
    abstract public function test(Integration $integration): string;

    /** Sends one message and returns the platform's message id. $to is what address() gave; $options may carry a template name. */
    abstract public function deliver(Integration $integration, string $to, string $text, array $options = []): string;

    /** The platform's own rule about who may be messaged, shown to the person. */
    abstract protected function rule(): string;

    /** What to type in the Person's field for this platform, for the form. */
    public function handleLabel(): string
    {
        return $this->label().' ID';
    }

    public function category(): string
    {
        return 'messaging';
    }

    /** Where this person is reached on the platform: their id on it (set on the person), or null when they have none. */
    public function address(Person $person): ?string
    {
        $v = trim((string) (($person->handles ?? [])[$this->channel()] ?? ''));

        return $v === '' ? null : $v;
    }

    public function describe(): array
    {
        return parent::describe() + ['channel' => $this->channel(), 'messaging' => true, 'max_length' => $this->maxLength(), 'handle_label' => $this->handleLabel()];
    }

    public function consentNote(): string
    {
        return 'Messages go only to people who are subscribed here and have an address on '.$this->label().'. '.$this->rule();
    }

    /** Nothing is imported from a messaging account. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since): array
    {
        return [[], null];
    }

    /** The error to show: the platform's own words where it gives them. */
    protected function reject(Response $response): IntegrationException
    {
        $j = $response->json() ?? [];
        $why = $j['error']['message'] ?? $j['detail'] ?? $j['title'] ?? (is_string($j['error'] ?? null) ? $j['error'] : null);

        return new IntegrationException($this->label().' refused the message'.($why ? ': '.mb_substr((string) $why, 0, 300) : ' (HTTP '.$response->status().')').'.');
    }
}
