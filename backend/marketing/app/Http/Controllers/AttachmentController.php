<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /** Uploads one file for the designer's Attachments block. */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.Attachments::MAX_KB, 'extensions:'.implode(',', Attachments::EXTENSIONS)],
        ], [
            'file.max' => 'Files can be up to '.(Attachments::MAX_KB / 1024).' MB.',
            'file.extensions' => 'That file type is not allowed. Use: '.implode(', ', Attachments::EXTENSIONS).'.',
        ]);

        return response(Attachments::store($request->file('file'), $request->user()->company_id), 201);
    }

    /** Attaches a file chosen from the media library (this company's own files only). */
    public function fromMedia(Request $request)
    {
        $data = $request->validate(['media_id' => 'required|integer']);
        $media = Media::findOrFail($data['media_id']); // company scope: another company's file is a 404
        $path = Storage::disk('local')->path($media->path);
        $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
        abort_unless(is_file($path), 404, 'That file is missing from the media library.');
        abort_unless(in_array($ext, Attachments::EXTENSIONS, true), 422, 'That file type cannot be attached to an email. Use: '.implode(', ', Attachments::EXTENSIONS).'.');
        abort_if(filesize($path) > Attachments::MAX_KB * 1024, 422, 'Attachments can be up to '.(Attachments::MAX_KB / 1024).' MB.');

        return response(Attachments::storeCopy($path, $media->file_name, $request->user()->company_id), 201);
    }
}
