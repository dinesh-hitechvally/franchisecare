<?php

namespace App\Http\Controllers;

use App\Services\Attachments;
use Illuminate\Http\Request;

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
}
