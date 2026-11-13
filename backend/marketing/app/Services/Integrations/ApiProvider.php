<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/**
 * Shared plumbing of the platforms that are read with simple GET requests and one API key or token.
 * A subclass says where the API lives (api), how it authenticates (headers or basic) and what to say when the key is refused (refused).
 */
abstract class ApiProvider extends Provider
{
    /** The API address without a trailing slash. Platforms with a store address use what was typed. */
    protected function api(Integration $i): string
    {
        return rtrim($i->store_url, '/');
    }

    abstract protected function headers(Integration $i): array;

    /** What to tell the person when the platform refuses the key. */
    abstract protected function refused(): string;

    /** [user, password] for HTTP basic authentication, when the platform uses it. */
    protected function basic(Integration $i): ?array
    {
        return null;
    }

    public function baseUrl(Integration $integration): string
    {
        return $this->api($integration);
    }

    /** Platforms without an address are told apart by their credentials and options; stores by their address. */
    public function identity(Integration $integration): string
    {
        return $this->usesUrl() ? parent::identity($integration) : sha1(json_encode($integration->secrets ?? []).'|'.json_encode($integration->setting('config') ?? []));
    }

    protected function get(Integration $i, string $path, array $query = [], array $headers = []): array
    {
        $options = ['headers' => $headers + $this->headers($i), 'query' => $query];
        if ($basic = $this->basic($i)) {
            $options['basic'] = $basic;
        }
        $response = $this->send($i, 'GET', $this->api($i).$path, $options);
        if (! $response->successful()) {
            throw $this->failed($response, $this->refused(), $this->label().' could not find that. Check the details you entered.');
        }

        return $response->json() ?? [];
    }

    protected function stamp(?CarbonInterface $since, string $format = 'Y-m-d\TH:i:s\Z'): ?string
    {
        return $since ? $since->copy()->utc()->format($format) : null;
    }

    /** "Ann Lee" → ['Ann', 'Lee'] */
    protected function split(?string $name): array
    {
        return array_pad(explode(' ', trim((string) $name), 2), 2, null);
    }

    /** The first value of a list of {key: value} rows, preferring one flagged primary. */
    protected function firstOf(?array $rows, string $key): ?string
    {
        $rows = array_values(array_filter((array) $rows, 'is_array'));
        $primary = collect($rows)->first(fn ($r) => ! empty($r['primary'])) ?? ($rows[0] ?? null);

        return $primary[$key] ?? null;
    }

    /** An option typed by the person that becomes part of an address: letters, digits, dashes only. */
    protected function slug(Integration $i, string $key, string $what): string
    {
        $v = trim($this->option($i, $key));
        if (! preg_match('/^[A-Za-z0-9_-]{1,80}$/', $v)) {
            throw new IntegrationException("That does not look like a valid $what.");
        }

        return $v;
    }
}
