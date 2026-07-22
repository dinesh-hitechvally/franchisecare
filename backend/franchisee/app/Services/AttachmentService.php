<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    /**
     * Upload a file and log it to the attachments table.
     *
     * @param UploadedFile $file
     * @param string $module
     * @param string|null $description
     * @param array|null $metadata
     * @return Attachment
     */
    public static function upload(UploadedFile $file, string $module, ?string $description = null, ?array $metadata = null): Attachment
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $uploadedBy = $user?->id ?? 0;

        // Determine directory: company_id/module
        $companyDir = $companyId ? (string)$companyId : '0';
        $cleanModule = trim($module, '/');
        $directory = "{$companyDir}/{$cleanModule}";

        // Make filename unique to avoid collisions
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        
        // Generate a clean/unique filename
        $safeName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '', $safeName);
        $fileName = time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;

        // Store the file on 'public' disk
        $filePath = $file->storeAs($directory, $fileName, 'public');

        // Create the Attachment database record
        return Attachment::create([
            'company_id' => $companyId,
            'file_name' => $fileName,
            'module' => $cleanModule,
            'file_path' => $filePath,
            'origional_name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'extension' => $extension,
            'uploaded_by' => $uploadedBy,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
