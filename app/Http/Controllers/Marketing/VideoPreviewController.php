<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        abort_unless(hash_equals(self::REEL_01_VERSION, $version), 404);

        $filename = self::REEL_01_VERSION.'.mp4';
        $path = storage_path('app/video-previews/reel-01-vitrine-social-midia/'.$filename);

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
