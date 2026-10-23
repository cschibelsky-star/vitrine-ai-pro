<?php

namespace App\Http\Controllers;

use App\Models\AiMediaGeneration;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class AiImagePreviewController extends Controller
{
    public function __invoke(AiMediaGeneration $generation): Response
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin() && $user->is_active !== false, 403);

        abort_unless($generation->capability === 'image_generation'
            && $generation->status === 'Concluído', 404);

        $path = (string) $generation->asset_path;
        abort_unless((bool) preg_match(
            '~^ai-generated/marketing/[0-9]{4}/[0-9]{2}/[0-9]{2}/[a-f0-9-]{36}\.(png|jpg|webp)$~D',
            $path
        ), 404);

        $disk = (string) data_get($generation->metadata, 'storage_disk', config('filesystems.default', 'local'));
        // Never allow a public URL, arbitrary path, or dynamic disk name from request parameters.
        abort_unless(array_key_exists($disk, (array) config('filesystems.disks', [])), 404);
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);
        abort_unless($storage->size($path) <= 20 * 1024 * 1024, 404);

        $bytes = $storage->get($path);
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        abort_unless(in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true), 404);

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
