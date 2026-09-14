<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class VideoPreviewController extends Controller
{
    private const REEL_01_VERSION = 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1';

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
        $requestedVersion = str_ends_with($version, '.mp4')
            ? substr($version, 0, -4)
            : $version;

        abort_unless(hash_equals(self::REEL_01_VERSION, $requestedVersion), 404);

        $filename = self::REEL_01_VERSION.'.mp4';
        $path = storage_path('app/video-previews/reel-01-vitrine-social-midia/'.$filename);

        if (! is_file($path) || ! is_readable($path)) {
            logger()->warning('Reel 01 public media file unavailable', [
                'version' => $version,
                'path' => $path,
                'exists' => is_file($path),
                'readable' => is_readable($path),
            ]);

            abort(404);
        }

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

    public function publicAsset(string $bucket, string $asset): BinaryFileResponse
    {
        abort_unless($bucket === basename($bucket) && $asset === basename($asset), 404);

        $path = storage_path('app/media-delivery/'.$bucket.'/'.$asset);

        if ((! is_file($path) || ! is_readable($path)) && $bucket === 'reels') {
            $legacyPath = storage_path('app/external-video-producer/reel-01-vitrine-social-midia/'.$asset);
            if (is_file($legacyPath) && is_readable($legacyPath)) {
                $path = $legacyPath;
            }
        }

        if (! is_file($path) || ! is_readable($path)) {
            error_log(sprintf(
                'Marketing media asset unavailable bucket=%s asset=%s exists=%s readable=%s',
                $bucket,
                $asset,
                is_file($path) ? 'yes' : 'no',
                is_readable($path) ? 'yes' : 'no',
            ));
            abort(404);
        }

        $extension = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            default => null,
        };
        abort_unless($mime !== null, 404);

        $response = response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$asset.'"',
            'Content-Length' => (string) filesize($path),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    public function __invoke(Request $request, string $version): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals(self::REEL_01_VERSION, $version), 404);

        $filename = self::REEL_01_VERSION.'.mp4';
        $path = storage_path('app/video-previews/reel-01-vitrine-social-midia/'.$filename);

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
