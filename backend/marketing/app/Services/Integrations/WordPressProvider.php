<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** WordPress: the site's registered users (members, subscribers, customers…), through the REST API with an Application Password. */
class WordPressProvider extends Provider
{
    /** The roles WordPress has by default; the import takes the chosen ones. Administrators are never included unless chosen. */
    public const ROLES = ['subscriber' => 'Subscriber', 'customer' => 'Customer (WooCommerce)', 'contributor' => 'Contributor', 'author' => 'Author', 'editor' => 'Editor', 'shop_manager' => 'Shop manager', 'administrator' => 'Administrator'];

    public function key(): string
    {
        return 'wordpress';
    }

    public function category(): string
    {
        return 'website';
    }

    public function label(): string
    {
        return 'WordPress';
    }

    public function description(): string
    {
        return 'Import the registered users of a WordPress site (members, subscribers) as People.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Site address', 'type' => 'text', 'placeholder' => 'https://your-site.com', 'help' => 'The address of your WordPress site, over https.', 'secret' => false],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'placeholder' => 'admin', 'help' => 'The WordPress user the application password belongs to. It must be allowed to list users (an administrator).', 'secret' => true],
            ['key' => 'app_password', 'label' => 'Application password', 'type' => 'password', 'placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx', 'help' => 'Created under Users → Profile → Application Passwords.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In WordPress admin open Users → Profile (your own profile) and scroll to Application Passwords.',
            'Type a name such as “FranchiseCare Marketing” and choose Add New Application Password.',
            'Copy the password that is shown (spaces are fine; it is shown only once).',
            'Paste your site address, your username and the password here. The site must use https.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'wordpress', 'asks' => ['url']];
    }

    public function consentNote(): string
    {
        return 'WordPress does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have their permission. Only the roles you choose below are imported.';
    }

    public function extraSettings(): array
    {
        return [['key' => 'roles', 'label' => 'Import these roles', 'type' => 'multi', 'options' => self::ROLES, 'default' => ['subscriber', 'customer'], 'help' => 'Only users with one of these roles become People.']];
    }

    protected function basic(Integration $i): array
    {
        return [$this->secret($i, 'username'), preg_replace('~\s+~', '', $this->secret($i, 'app_password'))];
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', rtrim($i->store_url, '/').'/wp-json/'.$path, ['basic' => $this->basic($i), 'query' => $query]);
        if (! $response->successful()) {
            throw $this->failed($response, 'WordPress refused the login. Check the username and the application password.', 'The WordPress REST API was not found at this address. Check it, and that pretty permalinks are on.');
        }

        return $response;
    }

    public function test(Integration $integration): string
    {
        $root = $this->send($integration, 'GET', rtrim($integration->store_url, '/').'/wp-json/');
        if (! $root->successful()) {
            throw $this->failed($root, 'The site refused the request.', 'That address is not a WordPress site (nothing answers at /wp-json/). Check the address and that pretty permalinks are on.');
        }
        $this->get($integration, 'wp/v2/users/me', ['context' => 'edit']); // proves the login
        $list = $this->send($integration, 'GET', rtrim($integration->store_url, '/').'/wp-json/wp/v2/users', ['basic' => $this->basic($integration), 'query' => ['per_page' => 1, 'context' => 'edit']]);
        if ($list->status() === 403) {
            throw new IntegrationException('This user is not allowed to list users. Use an administrator account.');
        }

        return (string) ($root->json('name') ?: parse_url($integration->store_url, PHP_URL_HOST));
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $perPage = 100): array
    {
        $page = max(1, (int) $cursor);
        $roles = array_values(array_intersect(array_keys(self::ROLES), (array) $integration->setting('roles', ['subscriber', 'customer'])));

        $response = $this->send($integration, 'GET', rtrim($integration->store_url, '/').'/wp-json/wp/v2/users', [
            'basic' => $this->basic($integration),
            'query' => array_filter(['context' => 'edit', 'per_page' => $perPage, 'page' => $page, 'orderby' => 'id', 'order' => 'asc', 'roles' => implode(',', $roles) ?: null]),
        ]);
        // asking for a page after the last one is an error in WordPress: it simply means the end
        if ($response->status() === 400 && $response->json('code') === 'rest_post_invalid_page_number') {
            return [[], null];
        }
        if (! $response->successful()) {
            throw $this->failed($response, 'WordPress refused the login. Check the username and the application password, and that the user may list users.', 'The WordPress users address was not found.');
        }

        $customers = [];
        foreach ((array) $response->json() as $u) {
            $first = $u['first_name'] ?? null;
            $last = $u['last_name'] ?? null;
            if (! $first && ! $last && ! empty($u['name'])) { // no separate names: split the display name
                [$first, $last] = array_pad(explode(' ', trim($u['name']), 2), 2, null);
            }
            $customers[] = $this->customer($u['id'] ?? '', $u['email'] ?? null, $first, $last, null, null);
        }
        $totalPages = (int) $response->header('X-WP-TotalPages');
        $more = $totalPages ? $page < $totalPages : count($customers) === $perPage;

        return [$customers, $more ? (string) ($page + 1) : null];
    }
}
