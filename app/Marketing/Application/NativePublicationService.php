<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/** Shared receiver for Marketing gallery and the Social product bridge. */
final class NativePublicationService
{
    public static function version(array $piece): string
    {
        return hash('sha256', json_encode([
            $piece['id'] ?? '', $piece['revision_count'] ?? 0,
            $piece['asset_url'] ?? '', $piece['provider_job_ref'] ?? '',
            $piece['final_path'] ?? '', $piece['director_job'] ?? [],
            $piece['type'] ?? '', $piece['format'] ?? '', $piece['channel'] ?? '', $piece['caption'] ?? '',
        ], JSON_THROW_ON_ERROR));
    }

    public function publishGallery(string $path, string $version, array $account): array
    {
        return \Illuminate\Support\Facades\Cache::lock('marketing-gallery:'.hash('sha256', $path), 120)->block(5, function () use ($path, $version, $account): array {
            $disk = Storage::disk('local');
            if (! $disk->exists($path)) { return ['ok' => false, 'status' => 'NOT_FOUND']; }
            $piece = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
            if (! in_array($piece['status'] ?? '', ['APROVADO', 'PLANEJADO_EDITORIAL', 'PUBLICADO'], true)
                || ! hash_equals($version, self::version($piece))) {
                return ['ok' => false, 'status' => 'REAPPROVAL_REQUIRED'];
            }
            return $this->publishApproved($piece, $account, dirname($path));
        });
    }

    public function hasAttempt(string $identity): bool
    {
        return Storage::disk('local')->exists($this->path($identity));
    }

    public function publishApproved(array $piece, array $account, string $scope): array
    {
        if (empty($piece['approved_at']) || empty($piece['approved_by'])
            || ! hash_equals((string) ($piece['approved_version'] ?? ''), self::version($piece))) {
            return ['ok' => false, 'status' => 'REAPPROVAL_REQUIRED'];
        }
        $channel = strtolower((string) ($piece['channel'] ?? 'instagram'));
        $format = strtolower((string) ($piece['format'] ?? 'feed'));
        $placement = match ($format) {
            'story', 'stories', 'story_9_16' => 'stories',
            'reel', 'reels', 'video', 'reel_9_16' => 'reels',
            'ad_1_1', 'feed', 'post', 'post_portrait', 'facebook_post', 'post_square', 'image' => 'feed',
            default => '',
        };
        $type = strtolower((string) ($piece['type'] ?? 'image'));
        $key = hash('sha256', $scope.'|'.$piece['id'].'|'.$piece['approved_version']);
        return $this->publish([
            'channel' => $channel, 'placement' => $placement, 'type' => $type,
            'asset_url' => (string) ($piece['asset_url'] ?? ''),
            'caption' => (string) ($piece['caption'] ?? data_get($piece, 'director_job.caption', '')),
            'mime_type' => (string) ($piece['publication_mime_type'] ?? ($type === 'video' ? 'video/mp4' : '')),
        ], $account, $scope.'|'.$piece['id'], $key);
    }

    public function capabilities(array $account): array
    {
        if (! config('marketing_publishing.enabled', false) || empty($account['access_token'])) { return []; }
        $supported = [];
        if (! empty($account['instagram_user_id'])) {
            $supported = ['instagram.feed.image', 'instagram.stories.image', 'instagram.stories.video', 'instagram.reels.video'];
        }
        if (! empty($account['facebook_page_id']) || ! empty($account['page_id'])) { $supported[] = 'facebook.feed.image'; }
        return array_values(array_intersect($supported, (array) config('marketing_publishing.capabilities', [])));
    }

    public function lookup(string $identity, string $key): array
    {
        $path = $this->path($identity);
        if (! Storage::disk('local')->exists($path)) { return ['ok' => false, 'status' => 'NOT_FOUND']; }
        $entry = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! hash_equals((string) ($entry['idempotency_key'] ?? ''), $key)) {
            return ['ok' => false, 'status' => 'IDEMPOTENCY_CONFLICT'];
        }
        return $this->result($entry);
    }

    public function publish(array $payload, array $account, string $identity, string $key): array
    {
        if (! config('marketing_publishing.enabled', false)) { return ['ok' => false, 'status' => 'DISABLED']; }
        $capability = ($payload['channel'] ?? '').'.'.($payload['placement'] ?? '').'.'.($payload['type'] ?? '');
        if (! in_array($capability, $this->capabilities($account), true)) { return ['ok' => false, 'status' => 'FORMAT_NOT_AUTHORIZED']; }
        $url = (string) ($payload['asset_url'] ?? '');
        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return ['ok' => false, 'status' => 'MEDIA_NOT_PUBLIC'];
        }
        if (($payload['channel'] ?? '') === 'instagram' && ($payload['type'] ?? '') === 'image'
            && ($payload['mime_type'] ?? '') !== 'image/jpeg') {
            return ['ok' => false, 'status' => 'JPEG_REVIEW_REQUIRED'];
        }
        if (($payload['placement'] ?? '') === 'reels' && ($payload['type'] ?? '') !== 'video') {
            return ['ok' => false, 'status' => 'INVALID_FORMAT'];
        }
        $base = rtrim((string) ($account['base_url'] ?? 'https://graph.facebook.com'), '/');
        if (! in_array($base, ['https://graph.facebook.com', 'https://graph.instagram.com'], true)) {
            return ['ok' => false, 'status' => 'INVALID_GRAPH_HOST'];
        }
        $version = (string) ($account['graph_version'] ?? '');
        if (! preg_match('/^v[0-9]+\.[0-9]+$/', $version)) { return ['ok' => false, 'status' => 'INVALID_GRAPH_VERSION']; }
        $hash = hash('sha256', json_encode([$payload, $account['instagram_user_id'] ?? '', $account['facebook_page_id'] ?? $account['page_id'] ?? ''], JSON_THROW_ON_ERROR));
        $disk = Storage::disk('local'); $disk->makeDirectory('marketing/publisher/native-ledger');
        $path = $this->path($identity);
        $lock = fopen($disk->path($path.'.lock'), 'c+');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) { fclose($lock); }
            return ['ok' => true, 'status' => 'PROCESSING', 'idempotency_key' => $key];
        }
        try {
            $entry = $disk->exists($path) ? json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR) : [];
            if ($entry !== []) {
                if (! hash_equals((string) $entry['request_hash'], $hash) || ! hash_equals((string) $entry['idempotency_key'], $key)) {
                    return ['ok' => false, 'status' => 'IDEMPOTENCY_CONFLICT'];
                }
                if (in_array($entry['status'], ['PUBLISHED', 'UNKNOWN'], true)) { return $this->result($entry); }
                if (in_array($entry['phase'], ['CREATE_SUBMITTED', 'PUBLISH_SUBMITTED'], true)) {
                    $entry['status'] = 'UNKNOWN'; $this->save($path, $entry); return $this->result($entry);
                }
            } else {
                $entry = ['request_hash' => $hash, 'idempotency_key' => $key, 'status' => 'PROCESSING', 'phase' => 'NEW',
                    'channel' => $payload['channel'], 'created_at' => now()->toIso8601String()];
            }
            $token = (string) $account['access_token'];
            if ($payload['channel'] === 'facebook') {
                $entry['phase'] = 'PUBLISH_SUBMITTED'; $this->save($path, $entry);
                $response = Http::asForm()->timeout(25)->post($base.'/'.$version.'/'.($account['facebook_page_id'] ?? $account['page_id']).'/photos', [
                    'access_token' => $token, 'url' => $url, 'caption' => $payload['caption'] ?? '', 'published' => 'true',
                ]);
                $external = (string) ($response->json('post_id') ?: $response->json('id', ''));
                if (! $response->successful() || $external === '') { throw new RuntimeException('Unconfirmed response'); }
                $entry['external_id'] = $external;
            } else {
                $ig = (string) $account['instagram_user_id'];
                if (empty($entry['container_id'])) {
                    $body = ['access_token' => $token];
                    if ($payload['placement'] !== 'stories') { $body['caption'] = $payload['caption'] ?? ''; }
                    if ($payload['placement'] === 'stories') { $body['media_type'] = 'STORIES'; }
                    if ($payload['placement'] === 'reels') { $body['media_type'] = 'REELS'; }
                    $body[$payload['type'] === 'video' ? 'video_url' : 'image_url'] = $url;
                    $entry['phase'] = 'CREATE_SUBMITTED'; $this->save($path, $entry);
                    $create = Http::asForm()->timeout(25)->post($base.'/'.$version.'/'.$ig.'/media', $body);
                    if (! $create->successful() || ! $create->json('id')) { throw new RuntimeException('Unconfirmed create'); }
                    $entry['container_id'] = (string) $create->json('id');
                    $entry['phase'] = 'CONTAINER_CREATED'; $this->save($path, $entry);
                }
                $status = Http::acceptJson()->timeout(15)->get($base.'/'.$version.'/'.$entry['container_id'], [
                    'fields' => 'status_code', 'access_token' => $token,
                ]);
                if (! $status->successful()) { return $this->result($entry); }
                if (in_array($status->json('status_code'), ['ERROR', 'EXPIRED'], true)) {
                    $entry['status'] = 'UNKNOWN'; $this->save($path, $entry); return $this->result($entry);
                }
                if ($status->json('status_code') !== 'FINISHED') { return $this->result($entry); }
                $entry['phase'] = 'PUBLISH_SUBMITTED'; $this->save($path, $entry);
                $response = Http::asForm()->timeout(25)->post($base.'/'.$version.'/'.$ig.'/media_publish', [
                    'creation_id' => $entry['container_id'], 'access_token' => $token,
                ]);
                if (! $response->successful() || ! $response->json('id')) { throw new RuntimeException('Unconfirmed publish'); }
                $entry['external_id'] = (string) $response->json('id');
            }
            $entry['status'] = 'PUBLISHED'; $entry['phase'] = 'CONFIRMED'; $this->save($path, $entry);
            return $this->result($entry);
        } catch (Throwable) {
            $entry['status'] = 'UNKNOWN'; $this->save($path, $entry); return $this->result($entry);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private function path(string $identity): string
    {
        return 'marketing/publisher/native-ledger/'.hash('sha256', $identity).'.json';
    }

    private function save(string $path, array $entry): void
    {
        $target = Storage::disk('local')->path($path);
        $tmp = tempnam(dirname($target), '.ledger-');
        if ($tmp === false) { throw new RuntimeException('Ledger unavailable'); }
        try {
            if (file_put_contents($tmp, json_encode($entry, JSON_THROW_ON_ERROR), LOCK_EX) === false || ! rename($tmp, $target)) {
                throw new RuntimeException('Ledger persistence failed');
            }
        } finally { if (is_file($tmp)) { unlink($tmp); } }
    }

    private function result(array $entry): array
    {
        return ['ok' => true, 'status' => $entry['status'], 'channel' => $entry['channel'],
            'external_id' => $entry['external_id'] ?? null, 'container_id' => $entry['container_id'] ?? null,
            'idempotency_key' => $entry['idempotency_key']];
    }
}
