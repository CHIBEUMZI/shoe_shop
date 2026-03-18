<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    protected array $allowedFolders = [
        'products',
        'brands',
        'categories',
        'avatars',
        'banners',
        'uploads',
    ];

    public function store(UploadImageRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $folder = $this->sanitizeFolder(
            $request->input('folder', 'uploads')
        );

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = now()->format('YmdHis') . '_' . Str::random(12) . '.' . $ext;

        $path = $file->storeAs($folder, $filename, 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'message' => 'Upload thành công',
            'data' => [
                'url' => $url,
                'path' => $path,
                'filename' => $filename,
                'folder' => $folder,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $this->normalizeStoragePath($request->input('path'));

        if (!$path) {
            return response()->json([
                'message' => 'Path không hợp lệ',
            ], 422);
        }

        $folder = explode('/', $path)[0] ?? '';

        if (!in_array($folder, $this->allowedFolders, true)) {
            return response()->json([
                'message' => 'Folder không được phép xoá',
            ], 422);
        }

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'message' => 'File không tồn tại',
            ], 404);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Xoá file thành công',
            'data' => [
                'path' => $path,
            ],
        ]);
    }

    protected function sanitizeFolder(?string $folder): string
    {
        $folder = strtolower(trim((string) $folder));
        $folder = str_replace('\\', '/', $folder);
        $folder = trim($folder, '/');

        if ($folder === '') {
            return 'uploads';
        }

        return in_array($folder, $this->allowedFolders, true)
            ? $folder
            : 'uploads';
    }

    protected function normalizeStoragePath(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parsedPath = parse_url($value, PHP_URL_PATH);
            $value = is_string($parsedPath) ? $parsedPath : '';
        }

        $value = str_replace('\\', '/', $value);

        if (Str::startsWith($value, '/storage/')) {
            $value = Str::replaceFirst('/storage/', '', $value);
        }

        if (Str::startsWith($value, 'storage/')) {
            $value = Str::replaceFirst('storage/', '', $value);
        }

        $value = trim($value, '/');

        if ($value === '' || Str::contains($value, '..')) {
            return null;
        }

        return $value;
    }
}