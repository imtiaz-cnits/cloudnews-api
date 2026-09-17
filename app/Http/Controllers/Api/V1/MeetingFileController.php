<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeetingFileController extends Controller
{
    /**
     * Upload a file to the meeting room.
     * Maximum size: 25MB (25600 KB)
     */
    public function upload(Request $request, string $meeting_code): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:25600', // 25 MB max limit
            'category' => 'nullable|string|in:image,video,audio,document',
        ]);

        $uploadedFile = $request->file('file');
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return $this->errorResponse('Invalid or corrupted file upload.', 422);
        }

        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'bin';
        $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
        $fileSizeBytes = $uploadedFile->getSize();

        // Categorize file if not provided
        $category = $request->input('category');
        if (!$category) {
            if (str_starts_with($mimeType, 'image/')) {
                $category = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $category = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $category = 'audio';
            } else {
                $category = 'document';
            }
        }

        // Clean file name and generate unique storage filename
        $safeName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeSlug = Str::slug($safeName) ?: 'file';
        $storageFilename = sprintf('%s_%s.%s', $safeSlug, Str::random(8), $extension);

        // Store file under public storage disk
        $directory = 'meeting-files/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $meeting_code);
        $path = $uploadedFile->storeAs($directory, $storageFilename, 'public');

        if (!$path) {
            return $this->errorResponse('Failed to store file on server.', 500);
        }

        // Generate full download URL
        // Prefer explicit APP_URL or root URL to guarantee reachable link from Mainland China
        $baseUrl = rtrim(config('app.url') ?: $request->root(), '/');
        $fileUrl = $baseUrl . '/storage/' . $path;

        // Human readable formatted size
        $formattedSize = $this->formatBytes($fileSizeBytes);

        return $this->successResponse([
            'file_name' => $originalName,
            'file_url' => $fileUrl,
            'file_size' => $fileSizeBytes,
            'file_size_formatted' => $formattedSize,
            'file_type' => $category,
            'mime_type' => $mimeType,
            'meeting_code' => $meeting_code,
            'uploaded_at' => now()->toIso8601String(),
        ], 'File uploaded successfully', 201);
    }

    /**
     * Fallback file download handler in case symbolic link is absent.
     */
    public function download(string $meeting_code, string $filename)
    {
        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $meeting_code);
        $safeFilename = basename($filename);
        $relativePath = 'meeting-files/' . $safeCode . '/' . $safeFilename;

        if (!Storage::disk('public')->exists($relativePath)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->response($relativePath);
    }

    /**
     * Helper to format bytes into readable string
     */
    private function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / pow(1024, $power);
        return round($value, $precision) . ' ' . $units[$power];
    }
}
