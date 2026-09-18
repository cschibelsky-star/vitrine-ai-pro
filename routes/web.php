<?php

use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\Marketing\VideoPreviewController;
use App\Marketing\Application\VideoFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /marketing/media/image/\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/marketing/media/reel-01/{version}', [VideoPreviewController::class, 'publicMedia'])
    ->middleware(['throttle:60,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-01');

Route::get('/marketing/media/reel-03/{version}', [VideoPreviewController::class, 'publicMediaReel03'])
    ->middleware(['throttle:30,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-03');

Route::get('/marketing/media/image/{filename}', [VideoPreviewController::class, 'publicImage'])
    ->middleware(['throttle:60,1'])
    ->where('filename', '[A-Za-z0-9._-]+\\.png')
    ->name('marketing.media.image');

Route::get('/marketing/video-preview/{version}', VideoPreviewController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->where('version', '[A-Za-z0-9._-]+')
    ->name('marketing.video-preview');

Route::post('/marketing/internal/finalize-reel-03', function (Request $request, VideoFinalizationService $service) {
    $expected = (string) env('VIDEO_FINALIZE_TOKEN', '');
    abort_unless($expected !== '' && hash_equals($expected, (string) $request->header('X-Vitrine-Finalize-Token', '')), 403);

    $source = (string) env('VIDEO_FINALIZE_SOURCE_URL', '');
    abort_unless($source !== '', 503, 'video_source_not_configured');

    return response()->json($service->finalizeFromUrl(
        'REEL-03-VITRINE-SOCIAL-MIDIA-20260915',
        'HEYGEN-9b99ff8586e8402087725968c63ab5dd-V1',
        $source,
        base_path('assets/img/logo-vitrine-ai-pro.png'),
    ));
})->middleware(['throttle:2,1'])->name('marketing.internal.finalize-reel-03');

Route::middleware(['auth'])->group(function () {
    Route::get('/marketing/video-preview/reel-01/signed', [VideoPreviewController::class, 'signedUrl'])
        ->middleware(['throttle:10,1'])
        ->name('marketing.video-preview.sign');

    Route::get('/marketing/native-preview/{job}/{version}', [VideoPreviewController::class, 'nativePreview'])
        ->middleware(['signed', 'throttle:30,1'])
        ->where('job', '[A-Za-z0-9._-]+')
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('marketing.native-video-preview');

    Route::get('/marketing/native-image-preview/{generation}', [VideoPreviewController::class, 'nativeImagePreview'])
        ->middleware(['signed', 'throttle:30,1'])
        ->whereNumber('generation')
        ->name('marketing.native-image-preview');

    Route::get('/cliente', [ClientPortalController::class, 'index'])->name('client.portal');

    Route::post('/cliente/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    })->name('client.logout');
});

if (file_exists(__DIR__.'/client_portal_auth.php')) require __DIR__.'/client_portal_auth.php';
if (file_exists(__DIR__.'/client_support_tickets.php')) require __DIR__.'/client_support_tickets.php';
if (file_exists(__DIR__.'/ai_run.php')) require __DIR__.'/ai_run.php';
if (file_exists(__DIR__.'/asaas.php')) require __DIR__.'/asaas.php';
if (file_exists(__DIR__.'/ai_provider_test.php')) require __DIR__.'/ai_provider_test.php';
if (file_exists(__DIR__.'/master_2_0.php')) require __DIR__.'/master_2_0.php';
if (file_exists(__DIR__.'/heygen_callback.php')) require __DIR__.'/heygen_callback.php';
