<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class VideoPreviewRouteTest extends TestCase
{
    private const VERSION = 'SESSION-REEL-01-VITRINE-SOCIAL-MIDIA-20260911-V1';

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('app/video-previews/reel-01-vitrine-social-midia');
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0770, true);
        }

        file_put_contents($this->directory.'/'.self::VERSION.'.mp4', 'test-mp4-content');
    }

    protected function tearDown(): void
    {
        @unlink($this->directory.'/'.self::VERSION.'.mp4');
        parent::tearDown();
    }

    public function test_signed_preview_serves_only_the_preserved_reel_inline(): void
    {
        $url = URL::temporarySignedRoute(
            'marketing.video-preview',
            now()->addMinutes(5),
            ['version' => self::VERSION],
        );

        $response = $this->get($url);

        $response->assertOk()
            ->assertHeader('content-type', 'video/mp4')
            ->assertHeader('cache-control', 'private, no-store, max-age=0')
            ->assertHeader('x-robots-tag', 'noindex, nofollow, noarchive');
    }

    public function test_unsigned_preview_is_forbidden(): void
    {
        $this->get(route('marketing.video-preview', ['version' => self::VERSION]))
            ->assertForbidden();
    }

    public function test_a_signed_unknown_version_is_not_found(): void
    {
        $url = URL::temporarySignedRoute(
            'marketing.video-preview',
            now()->addMinutes(5),
            ['version' => 'UNKNOWN-V1'],
        );

        $this->get($url)->assertNotFound();
    }
}
