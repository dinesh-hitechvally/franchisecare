<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Models\Media;
use App\Services\Attachments;
use App\Services\OnlineImageImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** The media library: upload, browse, rename and delete files. Pickers (company logo, …) choose from here. */
class MediaController extends Controller
{
    public function index(Request $request)
    {
        return Media::query()
            ->when($request->query('kind'), fn ($q, $v) => $q->where('kind', $v))
            ->when($request->query('origin') === 'uploaded', fn ($q) => $q->where('source', 'upload'))
            ->when($request->query('origin') === 'online', fn ($q) => $q->where('source', '!=', 'upload'))
            ->when($request->query('source'), fn ($q, $v) => $q->where('source', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('file_name', 'like', "%$v%")))
            ->when($request->query('from'), fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($request->query('to'), fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->latest('id')->paginate(min(60, max(6, (int) $request->query('per_page', 24))));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.Media::MAX_KB, 'extensions:'.implode(',', array_merge(Media::IMAGES, Media::DOCUMENTS))],
            'name' => 'nullable|string|max:255',
        ], [
            'file.max' => 'Files can be up to '.(Media::MAX_KB / 1024).' MB.',
            'file.extensions' => 'That file type is not allowed. Use: '.implode(', ', array_merge(Media::IMAGES, Media::DOCUMENTS)).'.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($ext, Media::IMAGES, true);
        $size = null;
        if ($isImage) {
            $size = @getimagesize($file->getRealPath());
            abort_unless($size, 422, 'That file is not a valid image.'); // the extension says image, the content must agree
        }

        $company = $request->user()->company_id;
        $token = Str::random(40);
        $fileName = Attachments::cleanName($file->getClientOriginalName());
        Storage::disk('local')->putFileAs("media/{$company}", $file, "{$token}.{$ext}");

        $media = (new Media)->forceFill([
            'company_id' => $company, 'token' => $token,
            'name' => trim((string) $request->input('name')) ?: pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName, 'path' => "media/{$company}/{$token}.{$ext}",
            'mime' => $isImage ? ($size['mime'] ?? $file->getMimeType()) : ($file->getMimeType() ?: 'application/octet-stream'),
            'kind' => $isImage ? 'image' : 'document', 'size' => $file->getSize(),
            'width' => $size[0] ?? null, 'height' => $size[1] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $media->save();

        return response($media->refresh(), 201);
    }

    /** Adds a free online image (one the image search just returned) to the library as its own copy, with its credit. */
    public function importOnline(Request $request)
    {
        $d = $request->validate([
            'url' => 'required|string|max:1000', 'thumb' => 'nullable|string|max:1000',
            'source' => 'required|string|max:40', 'author' => 'nullable|string|max:255', 'author_url' => 'nullable|string|max:500',
            'license' => 'nullable|string|max:100', 'link' => 'nullable|string|max:500', 'name' => 'nullable|string|max:255',
        ]);
        $company = $request->user()->company_id;
        $source = strtolower(strtok($d['source'], ' '));
        abort_unless(in_array($source, ['openverse', 'wikimedia', 'unsplash', 'pexels', 'pixabay'], true), 422, 'Unknown image source.');
        abort_unless(OnlineImageImport::isVouched($company, $d['url']), 422, 'Search for the image first, then add it.');

        // adding the same photo twice gives the copy you already have
        if (! empty($d['link']) && ($have = Media::where('source', $source)->where('source_url', $d['link'])->first())) {
            return response($have, 200);
        }

        try {
            try {
                [$data, $info] = OnlineImageImport::download($d['url']);
            } catch (\Throwable $first) {
                // the full-size original may be too big or gone: fall back to the preview if the search vouched for it too
                if (empty($d['thumb']) || ! OnlineImageImport::isVouched($company, $d['thumb'])) {
                    throw $first;
                }
                [$data, $info] = OnlineImageImport::download($d['thumb']);
            }
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        [$ext, $mime] = OnlineImageImport::TYPES[$info[2]];
        $token = Str::random(40);
        $author = trim((string) ($d['author'] ?? '')) ?: 'unknown';
        $title = trim((string) ($d['name'] ?? '')) ?: 'Photo by '.$author;
        Storage::disk('local')->put("media/{$company}/{$token}.{$ext}", $data);

        $media = (new Media)->forceFill([
            'company_id' => $company, 'token' => $token, 'name' => $title, 'file_name' => Attachments::cleanName($source.'-'.Str::slug($title).'.'.$ext),
            'path' => "media/{$company}/{$token}.{$ext}", 'mime' => $mime, 'kind' => 'image', 'size' => strlen($data),
            'width' => $info[0], 'height' => $info[1], 'alt' => $title,
            'source' => $source, 'credit' => $author, 'credit_url' => $d['author_url'] ?? null, 'license' => trim((string) ($d['license'] ?? '')) ?: null, 'source_url' => $d['link'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $media->save();

        return response($media->refresh(), 201);
    }

    public function update(Request $request, Media $media)
    {
        $media->update($request->validate(['name' => 'required|string|max:255', 'alt' => 'nullable|string|max:255', 'description' => 'nullable|string|max:5000']));

        return $media;
    }

    public function destroy(Request $request, Media $media)
    {
        // a deleted logo would silently turn back into the letter avatar: make the person choose another first
        abort_if((int) $request->user()->company->setting('profile.logo_media_id') === $media->id, 422, 'This is your company logo. Choose another logo first (Settings → Company profile), then delete it.');
        $media->delete(); // soft delete: the row and the file stay

        return response()->noContent();
    }

    /** The file itself, by its token. Public (an email or a page needs to load it), but only images are shown in the browser. */
    public function file(string $token)
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $token), 404);
        $media = Media::withoutGlobalScope('company')->where('token', $token)->firstOrFail();
        $path = Storage::disk('local')->path($media->path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => ($media->kind === 'image' ? 'inline' : 'attachment').'; filename="'.addslashes($media->file_name).'"',
        ]);
    }
}
