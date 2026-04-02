<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class AvatarUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                File::image()
                    ->max(2 * 1024), // max 2MB
            ],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            $oldPath = $this->normalizePath($user->avatar);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $file = $request->file('avatar');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'avatar_' . $user->id . '_' . now()->format('YmdHis') . '.' . $ext;

        $path = $file->storeAs('avatars', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        // Update user avatar
        $user->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Upload avatar thành công',
            'data' => [
                'url' => $url,
                'path' => $path,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->avatar) {
            return response()->json([
                'message' => 'Không có avatar để xóa',
            ], 400);
        }

        $path = $this->normalizePath($user->avatar);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'message' => 'Xóa avatar thành công',
        ]);
    }

    protected function normalizePath(?string $value): ?string
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
