<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Searches free image sources and normalises the results:
 * [{id, thumb, url, width, height, author, author_url, source, license, link}]
 *
 * Openverse and Wikimedia Commons need no API key and are always available.
 * Unsplash / Pexels / Pixabay switch on automatically when a server-wide key
 * is present in .env (see config/services.php) - companies never enter keys.
 */
class ImageSearch
{
    public const KEYLESS = ['openverse' => 'Openverse (free licences)', 'wikimedia' => 'Wikimedia Commons'];
    public const KEYED = ['unsplash' => 'Unsplash', 'pexels' => 'Pexels', 'pixabay' => 'Pixabay'];

    /** @return array<string,string> provider id => label, only those usable right now */
    public static function available(): array
    {
        $out = self::KEYLESS;
        foreach (self::KEYED as $id => $label) {
            if (config("services.images.{$id}")) {
                $out[$id] = $label;
            }
        }

        return $out;
    }

    public function search(string $provider, string $query, int $page = 1): array
    {
        if ($provider === 'all') {
            return $this->all($query, $page);
        }
        if (! isset(self::available()[$provider])) {
            throw new RuntimeException("Image source '{$provider}' is not available.");
        }

        return $this->{$provider}($query, $page);
    }

    /** Queries every available source, skips the ones that fail, interleaves the rest. */
    protected function all(string $query, int $page): array
    {
        $sets = [];
        $errors = [];
        foreach (array_keys(self::available()) as $id) {
            try {
                $sets[] = $this->{$id}($query, $page);
            } catch (\Throwable $e) {
                $errors[] = "{$id}: ".$e->getMessage();
            }
        }

        if (! $sets) {
            throw new RuntimeException('No image source responded. '.implode('; ', $errors));
        }

        $out = [];
        for ($i = 0, $max = max(array_map('count', $sets)); $i < $max; $i++) {
            foreach ($sets as $set) {
                if (isset($set[$i])) {
                    $out[] = $set[$i];
                }
            }
        }

        return $out;
    }

    protected function http()
    {
        return Http::withHeaders(['User-Agent' => 'FranchiseCareMarketing/1.0 (image search)'])->timeout(8);
    }

    protected function openverse(string $q, int $page): array
    {
        $res = $this->http()->get('https://api.openverse.org/v1/images/', ['q' => $q, 'page' => $page, 'page_size' => 20, 'mature' => 'false'])
            ->throw()->json();

        return array_map(fn ($p) => [
            'id' => $p['id'],
            'thumb' => $p['thumbnail'] ?? $p['url'],
            'url' => $p['url'],
            'width' => $p['width'] ?? null,
            'height' => $p['height'] ?? null,
            'author' => $p['creator'] ?? 'Unknown',
            'author_url' => $p['creator_url'] ?? $p['foreign_landing_url'],
            'source' => 'Openverse',
            'license' => strtoupper($p['license'] ?? '').' '.($p['license_version'] ?? ''),
            'link' => $p['foreign_landing_url'],
        ], $res['results'] ?? []);
    }

    protected function wikimedia(string $q, int $page): array
    {
        $res = $this->http()->get('https://commons.wikimedia.org/w/api.php', [
            'action' => 'query', 'format' => 'json', 'generator' => 'search', 'gsrnamespace' => 6,
            'gsrsearch' => $q.' filetype:bitmap', 'gsrlimit' => 24, 'gsroffset' => ($page - 1) * 24,
            'prop' => 'imageinfo', 'iiprop' => 'url|size|extmetadata', 'iiurlwidth' => 800,
        ])->throw()->json();

        $out = [];
        $pages = $res['query']['pages'] ?? [];
        uasort($pages, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0)); // keep search relevance order
        foreach ($pages as $p) {
            $i = $p['imageinfo'][0] ?? null;
            // urls carry a ?utm_ query string, so test the path only
            if (! $i || ! preg_match('/\.(jpe?g|png)$/i', parse_url($i['url'], PHP_URL_PATH) ?? '')) {
                continue;
            }
            $meta = $i['extmetadata'] ?? [];
            $out[] = [
                'id' => (string) $p['pageid'],
                'thumb' => $i['thumburl'] ?? $i['url'],
                'url' => $i['thumburl'] ?? $i['url'],
                'width' => $i['thumbwidth'] ?? $i['width'],
                'height' => $i['thumbheight'] ?? $i['height'],
                'author' => trim(strip_tags($meta['Artist']['value'] ?? 'Wikimedia contributor')),
                'author_url' => $i['descriptionurl'],
                'source' => 'Wikimedia Commons',
                'license' => $meta['LicenseShortName']['value'] ?? '',
                'link' => $i['descriptionurl'],
            ];
        }

        return $out;
    }

    protected function unsplash(string $q, int $page): array
    {
        $res = $this->http()->withHeaders(['Authorization' => 'Client-ID '.config('services.images.unsplash')])
            ->get('https://api.unsplash.com/search/photos', ['query' => $q, 'page' => $page, 'per_page' => 24])
            ->throw()->json();

        return array_map(fn ($p) => [
            'id' => $p['id'],
            'thumb' => $p['urls']['small'],
            'url' => $p['urls']['regular'],
            'width' => $p['width'],
            'height' => $p['height'],
            'author' => $p['user']['name'],
            'author_url' => $p['user']['links']['html'].'?utm_source=franchisecare_marketing&utm_medium=referral',
            'source' => 'Unsplash',
            'license' => '',
            'link' => $p['links']['html'],
        ], $res['results'] ?? []);
    }

    protected function pexels(string $q, int $page): array
    {
        $res = $this->http()->withHeaders(['Authorization' => config('services.images.pexels')])
            ->get('https://api.pexels.com/v1/search', ['query' => $q, 'page' => $page, 'per_page' => 24])
            ->throw()->json();

        return array_map(fn ($p) => [
            'id' => (string) $p['id'],
            'thumb' => $p['src']['medium'],
            'url' => $p['src']['large'],
            'width' => $p['width'],
            'height' => $p['height'],
            'author' => $p['photographer'],
            'author_url' => $p['photographer_url'],
            'source' => 'Pexels',
            'license' => '',
            'link' => $p['url'],
        ], $res['photos'] ?? []);
    }

    protected function pixabay(string $q, int $page): array
    {
        $res = $this->http()->get('https://pixabay.com/api/', ['key' => config('services.images.pixabay'), 'q' => $q, 'page' => $page, 'per_page' => 24, 'image_type' => 'photo', 'safesearch' => 'true'])
            ->throw()->json();

        return array_map(fn ($p) => [
            'id' => (string) $p['id'],
            'thumb' => $p['webformatURL'],
            'url' => $p['largeImageURL'],
            'width' => $p['imageWidth'],
            'height' => $p['imageHeight'],
            'author' => $p['user'],
            'author_url' => "https://pixabay.com/users/{$p['user']}-{$p['user_id']}/",
            'source' => 'Pixabay',
            'license' => '',
            'link' => $p['pageURL'],
        ], $res['hits'] ?? []);
    }
}
