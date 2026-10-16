<?php
namespace Tests\Unit\Marketing;

use App\Marketing\Application\NativePublicationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NativePublicationServiceTest extends TestCase
{
    private array $account;
    private array $payload;
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local'); Http::preventStrayRequests();
        config(['marketing_publishing.enabled' => true, 'marketing_publishing.capabilities' => [
            'instagram.feed.image', 'instagram.stories.image', 'instagram.stories.video', 'instagram.reels.video', 'facebook.feed.image',
        ]]);
        $this->account = ['access_token' => 'test-token', 'instagram_user_id' => 'ig-1', 'facebook_page_id' => 'page-1',
            'base_url' => 'https://graph.facebook.com', 'graph_version' => 'v26.0'];
        $this->payload = ['channel' => 'instagram', 'placement' => 'feed', 'type' => 'image',
            'mime_type' => 'image/jpeg', 'asset_url' => 'https://cdn.example.test/final.jpg', 'caption' => 'Approved caption'];
    }
    private function fake(string $status = 'FINISHED'): void
    {
        Http::fake([
            '*/ig-1/media' => Http::response(['id' => 'container-1']),
            '*/container-1*' => Http::response(['status_code' => $status]),
            '*/ig-1/media_publish' => Http::response(['id' => 'published-1']),
            '*/page-1/photos' => Http::response(['id' => 'photo-1', 'post_id' => 'post-1']),
        ]);
    }
    public function test_same_piece_only_publishes_once(): void
    {
        $this->fake(); $service = new NativePublicationService;
        $first = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $second = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('PUBLISHED', $first['status']); $this->assertSame($first, $second); Http::assertSentCount(3);
    }
    public function test_story_uses_stories_container_without_caption(): void
    {
        $this->fake(); $payload = $this->payload; $payload['placement'] = 'stories';
        (new NativePublicationService)->publish($payload, $this->account, 'marketing|story', 'key-1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/media')
            && $request['media_type'] === 'STORIES' && ! isset($request['caption']) && isset($request['image_url']));
    }
    public function test_reel_uses_reels_video_container(): void
    {
        $this->fake(); $payload = $this->payload;
        $payload['placement'] = 'reels'; $payload['type'] = 'video'; $payload['mime_type'] = 'video/mp4';
        (new NativePublicationService)->publish($payload, $this->account, 'marketing|reel', 'key-1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/media') && $request['media_type'] === 'REELS' && isset($request['video_url']));
    }
    public function test_pending_video_resumes_same_container_without_new_upload(): void
    {
        Http::fake([
            '*/ig-1/media' => Http::response(['id' => 'container-1']),
            '*/container-1*' => Http::sequence()->push(['status_code' => 'IN_PROGRESS'])->push(['status_code' => 'FINISHED']),
            '*/ig-1/media_publish' => Http::response(['id' => 'published-1']),
        ]);
        $service = new NativePublicationService;
        $result = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('PROCESSING', $result['status']); Http::assertSentCount(2);
        $result = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('PUBLISHED', $result['status']);
        Http::assertSentCount(4); // total: create once, two status reads, one publish
    }
    public function test_uncertain_submission_never_resends(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/media')) { return Http::response(['id' => 'container-1']); }
            if ($request->method() === 'GET') { return Http::response(['status_code' => 'FINISHED']); }
            throw new \Illuminate\Http\Client\ConnectionException('simulated timeout');
        });
        $service = new NativePublicationService;
        $result = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('UNKNOWN', $result['status']);
        $second = $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('UNKNOWN', $second['status']);
        $this->assertSame(1, count(Http::recorded(fn ($request) => str_ends_with($request->url(), '/media'))));
    }
    public function test_lookup_has_no_external_effect(): void
    {
        $this->fake(); $service = new NativePublicationService;
        $service->publish($this->payload, $this->account, 'social|1|2', 'key-1');
        $this->assertSame('PUBLISHED', $service->lookup('social|1|2', 'key-1')['status']);
        Http::assertSentCount(3);
        $this->assertFalse($service->lookup('social|1|2', 'different-key')['ok']);
    }
    public function test_changed_payload_cannot_reuse_piece_identity(): void
    {
        $this->fake(); $service = new NativePublicationService;
        $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $payload = $this->payload; $payload['caption'] = 'Changed';
        $this->assertSame('IDEMPOTENCY_CONFLICT', $service->publish($payload, $this->account, 'marketing|piece-1', 'key-1')['status']);
        Http::assertSentCount(3);
    }
    public function test_products_are_isolated(): void
    {
        $this->fake(); $service = new NativePublicationService;
        $service->publish($this->payload, $this->account, 'marketing|piece-1', 'key-1');
        $this->assertSame('NOT_FOUND', $service->lookup('social|1|piece-1', 'key-1')['status']);
    }
    public function test_external_publication_disabled_by_default_gate(): void
    {
        config(['marketing_publishing.enabled' => false]);
        $this->assertSame('DISABLED', (new NativePublicationService)->publish($this->payload, $this->account, 'marketing|piece', 'key')['status']);
        Http::assertNothingSent();
    }
    public function test_unsupported_facebook_story_does_not_fall_back_to_feed(): void
    {
        $payload = $this->payload; $payload['channel'] = 'facebook'; $payload['placement'] = 'stories';
        $this->assertSame('FORMAT_NOT_AUTHORIZED', (new NativePublicationService)->publish($payload, $this->account, 'marketing|piece', 'key')['status']);
        Http::assertNothingSent();
    }
    public function test_facebook_selection_does_not_publish_to_instagram(): void
    {
        $this->fake(); $payload = $this->payload; $payload['channel'] = 'facebook';
        $result = (new NativePublicationService)->publish($payload, $this->account, 'marketing|piece', 'key');
        $this->assertSame('facebook', $result['channel']); $this->assertSame('post-1', $result['external_id']); Http::assertSentCount(1);
    }
    public function test_png_requires_prepared_reviewed_jpeg_for_instagram(): void
    {
        $payload = $this->payload; $payload['mime_type'] = 'image/png';
        $this->assertSame('JPEG_REVIEW_REQUIRED', (new NativePublicationService)->publish($payload, $this->account, 'marketing|piece', 'key')['status']);
        Http::assertNothingSent();
    }
    public function test_approval_includes_format_channel_and_caption(): void
    {
        $piece = ['id' => 'piece-1', 'type' => 'image', 'format' => 'ad_1_1', 'channel' => 'instagram', 'caption' => 'Original'];
        $version = NativePublicationService::version($piece); $piece['caption'] = 'Edited';
        $this->assertNotSame($version, NativePublicationService::version($piece));
        $piece['approved_version'] = $version; $piece['approved_at'] = now()->toISOString(); $piece['approved_by'] = 1;
        $this->assertSame('REAPPROVAL_REQUIRED', (new NativePublicationService)->publishApproved($piece, $this->account, 'marketing')['status']);
        Http::assertNothingSent();
    }
    public function test_gallery_rechecks_current_approval_before_publication(): void
    {
        $piece = ['id' => 'piece-1', 'type' => 'image', 'format' => 'ad_1_1', 'channel' => 'instagram',
            'asset_url' => 'https://cdn.example.test/final.jpg', 'caption' => 'Original', 'status' => 'APROVADO'];
        $version = NativePublicationService::version($piece);
        $piece['approved_version'] = $version; $piece['approved_at'] = now()->toISOString(); $piece['approved_by'] = 1;
        $piece['status'] = 'EM_QA';
        Storage::disk('local')->put('marketing/gallery/scope/piece.json', json_encode($piece));
        $this->assertSame('REAPPROVAL_REQUIRED', (new NativePublicationService)->publishGallery('marketing/gallery/scope/piece.json', $version, $this->account)['status']);
        Http::assertNothingSent();
    }

    private function bridgeConnection(int $client = 1, string $user = 'meta-owner'): string
    {
        $path = 'marketing/publisher/meta/bridge/connections/'.hash('sha256', 'vitrine-ai-social-enterprise|'.$client).'.enc';
        Storage::disk('local')->put($path, \Illuminate\Support\Facades\Crypt::encryptString(json_encode(
            $this->account + ['page_id' => 'page-1', 'meta_user_id' => $user], JSON_THROW_ON_ERROR
        )));
        return $path;
    }

    private function bridgePayload(int $client = 1): array
    {
        return $this->payload + ['contract_version' => 2, 'project_id' => 'vitrine-ai-social-enterprise',
            'client_id' => $client, 'content_project_id' => 42,
            'asset_sha256' => str_repeat('a', 64), 'idempotency_key' => str_repeat('b', 64)];
    }

    private function bridgeRequest(array $payload, string $endpoint = 'publish')
    {
        // Fake credentials exist only for this request; all provider traffic remains Http::fake.
        putenv('MARKETING_ENGINE_TOKEN=bridge-test-token');
        try {
            return $this->withToken('bridge-test-token')->postJson('/api/internal/marketing/publisher/meta/'.$endpoint, $payload);
        } finally {
            putenv('MARKETING_ENGINE_TOKEN');
        }
    }

    public function test_bridge_v2_routes_accept_formats_and_deduplicate(): void
    {
        $this->fake(); $this->bridgeConnection();
        config(['marketing_agents.publisher.meta.graph_version' => 'v26.0']);
        $cases = [
            ['instagram', 'feed', 'image', 'image/jpeg'],
            ['instagram', 'stories', 'image', 'image/jpeg'],
            ['instagram', 'stories', 'video', 'video/mp4'],
            ['instagram', 'reels', 'video', 'video/mp4'],
            ['facebook', 'feed', 'image', 'image/png'],
        ];
        foreach ($cases as $i => [$channel, $placement, $type, $mime]) {
            $payload = array_replace($this->bridgePayload(), compact('channel', 'placement', 'type'),
                ['mime_type' => $mime, 'content_project_id' => 100 + $i]);
            $this->bridgeRequest($payload)->assertOk()->assertJson(['status' => 'PUBLISHED', 'channel' => $channel]);
            $this->bridgeRequest($payload)->assertOk()->assertJson(['status' => 'PUBLISHED']);
        }
        Http::assertSentCount(13); // Four Instagram containers, one Facebook photo, no duplicate sends.
    }

    public function test_bridge_cannot_use_another_clients_connection_or_ledger(): void
    {
        $this->fake(); $this->bridgeConnection(1);
        config(['marketing_agents.publisher.meta.graph_version' => 'v26.0']);
        $this->bridgeRequest($this->bridgePayload(2))->assertStatus(409);
        Http::assertNothingSent();
        $this->bridgeRequest($this->bridgePayload(1))->assertJson(['status' => 'PUBLISHED']);
        $this->bridgeRequest($this->bridgePayload(2), 'publication-status')->assertJson(['status' => 'NOT_FOUND']);
        Http::assertSentCount(3);
    }

    public function test_bridge_rejects_legacy_contract_and_unsupported_facebook_formats(): void
    {
        $this->bridgeConnection();
        $this->bridgeRequest(array_replace($this->bridgePayload(), ['contract_version' => 1]))->assertStatus(422);
        foreach (['stories', 'reels'] as $placement) {
            $this->bridgeRequest(array_replace($this->bridgePayload(), ['channel' => 'facebook', 'placement' => $placement]))
                ->assertJson(['status' => 'FORMAT_NOT_AUTHORIZED']);
        }
        Http::assertNothingSent();
    }

    public function test_bridge_resume_command_reuses_container_and_lookup_never_sends(): void
    {
        config(['marketing_agents.publisher.meta.graph_version' => 'v26.0']);
        $this->bridgeConnection();
        Http::fake([
            '*/ig-1/media' => Http::response(['id' => 'container-1']),
            '*/container-1*' => Http::sequence()->push(['status_code' => 'IN_PROGRESS'])->push(['status_code' => 'FINISHED']),
            '*/ig-1/media_publish' => Http::response(['id' => 'published-1']),
        ]);
        $payload = array_replace($this->bridgePayload(), ['placement' => 'reels', 'type' => 'video', 'mime_type' => 'video/mp4']);
        $this->bridgeRequest($payload)->assertJson(['status' => 'PROCESSING']);
        $this->bridgeRequest($payload, 'publication-status')->assertJson(['status' => 'PROCESSING']);
        Http::assertSentCount(2);
        $this->artisan('marketing:resume-native-publications')->assertExitCode(0);
        $this->bridgeRequest($payload, 'publication-status')->assertJson(['status' => 'PUBLISHED']);
        $this->artisan('marketing:resume-native-publications')->assertExitCode(0);
        Http::assertSentCount(4);
    }

    public function test_meta_revocation_removes_nested_social_connections_only_for_owner(): void
    {
        $owned = $this->bridgeConnection(1, 'owner-a');
        $other = $this->bridgeConnection(2, 'owner-b');
        $legacy = 'marketing/publisher/meta/legacy.enc';
        Storage::disk('local')->put($legacy, \Illuminate\Support\Facades\Crypt::encryptString(
            json_encode(['meta_user_id' => 'owner-a'], JSON_THROW_ON_ERROR)
        ));
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $payload = $encode(json_encode(['algorithm' => 'HMAC-SHA256', 'user_id' => 'owner-a'], JSON_THROW_ON_ERROR));
        $signed = $encode(hash_hmac('sha256', $payload, 'test-app-secret', true)).'.'.$payload;
        putenv('META_APP_SECRET=test-app-secret');
        try {
            $this->postJson('/api/marketing/publisher/meta/deauthorize', ['signed_request' => $signed])->assertOk();
        } finally {
            putenv('META_APP_SECRET');
        }
        Storage::disk('local')->assertMissing($owned);
        Storage::disk('local')->assertMissing($legacy);
        Storage::disk('local')->assertExists($other);
        Http::assertNothingSent();
    }
}
