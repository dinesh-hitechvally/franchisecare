<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Files that go out as real email attachments.
 *
 * The designer's "Attachments" block uploads files here and leaves <!--fc-attach:token,token--> in the email's HTML.
 * When a message is sent we read the tokens back out of that HTML, so campaigns, templates and automations all work
 * the same way. Files live under a per-company folder; a token can only be resolved inside its own company's folder.
 */
class Attachments
{
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'zip'];

    public const MAX_KB = 5120;       // per file

    public const MAX_FILES = 10;      // per email

    public const MAX_TOTAL = 15 * 1024 * 1024; // all files of one email

    protected static function dir(int $companyId): string
    {
        return 'attachments/'.$companyId;
    }

    /** Stores an upload and returns what the designer keeps: token, original name, size in bytes. */
    public static function store(UploadedFile $file, int $companyId): array
    {
        $token = bin2hex(random_bytes(16));
        $name = self::cleanName($file->getClientOriginalName());
        Storage::disk('local')->putFileAs(self::dir($companyId), $file, $token.'-'.$name);

        return ['token' => $token, 'name' => $name, 'size' => $file->getSize()];
    }

    public static function cleanName(string $name): string
    {
        $name = preg_replace('/[^\w.\- ()]+/u', '_', basename(str_replace('\\', '/', $name)));
        $name = ltrim(trim($name), '.');

        return $name !== '' ? Str::limit($name, 120, '') : 'file';
    }

    /** Tokens of a list like [{token, ...}] as an HTML marker, or '' when empty. */
    public static function marker(?array $list): string
    {
        $tokens = array_column($list ?? [], 'token');

        return $tokens ? '<!--fc-attach:'.implode(',', $tokens).'-->' : '';
    }

    /** What a request may keep: only files this company really uploaded, as [{token, name, size}] (null when none). */
    public static function clean(?array $list, int $companyId): ?array
    {
        $files = self::in(self::marker($list), $companyId);

        return $files ? array_map(fn ($f) => ['token' => $f['token'], 'name' => $f['name'], 'size' => $f['size']], $files) : null;
    }

    /** Files referenced by markers in $html, as [path, name, mime] (unknown or foreign tokens are ignored). */
    public static function in(string $html, int $companyId): array
    {
        if (! preg_match_all('/<!--fc-attach:([0-9a-f,]+)-->/', $html, $m)) {
            return [];
        }
        $tokens = array_slice(array_unique(array_filter(explode(',', implode(',', $m[1])), fn ($t) => preg_match('/^[0-9a-f]{32}$/', $t))), 0, self::MAX_FILES);

        $disk = Storage::disk('local');
        $out = [];
        $total = 0;
        foreach ($tokens as $token) {
            $found = glob($disk->path(self::dir($companyId)).DIRECTORY_SEPARATOR.$token.'-*');
            $path = $found[0] ?? null;
            if (! $path || ! is_file($path) || $total + filesize($path) > self::MAX_TOTAL) {
                continue;
            }
            $total += filesize($path);
            $out[] = ['token' => $token, 'size' => filesize($path), 'path' => $path, 'name' => substr(basename($path), 33), 'mime' => mime_content_type($path) ?: 'application/octet-stream'];
        }

        return $out;
    }
}
