<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Copies a free online image (found with the image search) into the media library.
 *
 * The server downloads it, so the address is never taken on trust:
 *  - only addresses that the image search itself just returned for this company can be imported ("vouched"),
 *  - the host must be a public one (no private / local addresses, also after redirects),
 *  - the download is size-limited, and the bytes must really be a PNG, JPG, GIF or WebP.
 */
class OnlineImageImport
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    public const TYPES = [IMAGETYPE_PNG => ['png', 'image/png'], IMAGETYPE_JPEG => ['jpg', 'image/jpeg'], IMAGETYPE_GIF => ['gif', 'image/gif'], IMAGETYPE_WEBP => ['webp', 'image/webp']];

    /** Remember the addresses a search returned, so only those can be imported afterwards. */
    public static function vouch(int $companyId, array $results): void
    {
        foreach ($results as $r) {
            foreach (['url', 'thumb'] as $k) {
                if (! empty($r[$k])) {
                    Cache::put(self::key($companyId, $r[$k]), true, 3600);
                }
            }
        }
    }

    public static function isVouched(int $companyId, ?string $url): bool
    {
        return $url !== null && $url !== '' && Cache::has(self::key($companyId, $url));
    }

    protected static function key(int $companyId, string $url): string
    {
        return "img-ok:{$companyId}:".sha1($url);
    }

    /** @return array{0: string, 1: array} the image bytes and getimagesizefromstring() info */
    public static function download(string $url): array
    {
        UrlGuard::assertPublic($url);

        $res = Http::withHeaders(['User-Agent' => 'FranchiseCareMarketing/1.0 (media import)'])->timeout(20)->withOptions([
            'stream' => true,
            'allow_redirects' => [
                'max' => 3, 'protocols' => ['http', 'https'],
                'on_redirect' => fn ($request, $response, $uri) => UrlGuard::assertPublicHost($uri->getHost()), // a redirect cannot lead somewhere private
            ],
        ])->get($url);
        if (! $res->successful()) {
            throw new RuntimeException('The image could not be downloaded (HTTP '.$res->status().').');
        }

        $body = $res->toPsrResponse()->getBody();
        $data = '';
        while (! $body->eof()) {
            $data .= $body->read(65536);
            if (strlen($data) > self::MAX_BYTES) {
                throw new RuntimeException('That image is larger than '.(self::MAX_BYTES / 1048576).' MB.');
            }
        }

        $info = @getimagesizefromstring($data);
        if (! $info || ! isset(self::TYPES[$info[2]])) {
            throw new RuntimeException('That is not a PNG, JPG, GIF or WebP image.');
        }

        return [$data, $info];
    }
}
