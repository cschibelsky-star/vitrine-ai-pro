<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\AiMediaGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VideoPreviewController extends Controller
{
    private const REEL_01_VERSION = 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1';
    private const REEL_03_VERSION = 'HEYGEN-9b99ff8586e8402087725968c63ab5dd-V1';

    public function signedUrl(): JsonResponse
    {
        $expiresAt = now()->addMinutes(60);

        return response()->json([
            'project_id' => 'vitrine-marketing-agents-core-hml',
            'version_id' => self::REEL_01_VERSION,
            'expires_at' => $expiresAt->toIso8601String(),
            'signed_url' => URL::temporarySignedRoute(
                'marketing.video-preview',
                $expiresAt,
                ['version' => self::REEL_01_VERSION],
            ),
        ], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function publicMedia(string $version): BinaryFileResponse
    {
        $requestedVersion = str_ends_with($version, '.mp4') ? substr($version, 0, -4) : $version;
        abort_unless(hash_equals(self::REEL_01_VERSION, $requestedVersion), 404);

        $filename = self::REEL_01_VERSION.'.mp4';
        $path = '/var/www/video-previews/reel-01-vitrine-social-midia/'.$filename;
        abort_unless(is_file($path) && is_readable($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    public function publicMediaReel03(string $version): BinaryFileResponse
    {
        $requestedVersion = str_ends_with($version, '.mp4') ? substr($version, 0, -4) : $version;
        abort_unless(hash_equals(self::REEL_03_VERSION.'-branded', $requestedVersion), 404);

        $filename = self::REEL_03_VERSION.'-branded.mp4';
        $path = storage_path('app/marketing/video-producer/reel-03-vitrine-social-midia-20260915/'.$filename);
        abort_unless(is_file($path) && is_readable($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="reel-03-vitrine-social-midia-final.mp4"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function publicImage(string $filename): BinaryFileResponse
    {
        $allowed = [
            'vitrine-ia-pro-logo-master.png' => base_path('assets/img/logo-vitrine-ai-pro.png'),
        ];

        abort_unless(array_key_exists($filename, $allowed), 404);

        $path = $allowed[$filename];
        abort_unless(is_file($path) && is_readable($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    public function nativeImagePreview(Request $request, string $generation): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(ctype_digit($generation), 404);

        $media = AiMediaGeneration::query()->findOrFail((int) $generation);
        abort_unless((string) $media->capability === 'image_generation', 404);
        abort_unless((string) $media->status === 'Concluído', 404);

        $path = trim((string) $media->asset_path);
        abort_unless($path !== '', 404);

        $disk = (string) data_get($media->metadata, 'storage_disk', config('filesystems.default', 'local'));
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $absolutePath = Storage::disk($disk)->path($path);
        abort_unless(is_file($absolutePath) && is_readable($absolutePath), 404);

        $response = response()->file($absolutePath, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="marketing-ia-criativo-'.$generation.'.png"',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function nativePreview(Request $request, string $job, string $version): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless((bool) preg_match('/^[A-Za-z0-9._-]+$/', $job), 404);
        abort_unless((bool) preg_match('/^[A-Za-z0-9._-]+$/', $version), 404);

        $root = (string) config('marketing_video.finalization.working_directory', '');
        $root = $root !== '' ? rtrim($root, '/') : storage_path('app/marketing/media');
        $path = $root.'/'.$job.'/'.$version.'/final.mp4';
        abort_unless(is_file($path) && is_readable($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.$job.'-'.$version.'-final.mp4"',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function __invoke(Request $request, string $version): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals(self::REEL_01_VERSION, $version), 404);

        $filename = self::REEL_01_VERSION.'.mp4';
        $mountedPath = '/var/www/video-previews/reel-01-vitrine-social-midia/'.$filename;
        $localPath = storage_path('app/video-previews/reel-01-vitrine-social-midia/'.$filename);
        $path = is_file($mountedPath) && is_readable($mountedPath) ? $mountedPath : $localPath;
        abort_unless(is_file($path) && is_readable($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
