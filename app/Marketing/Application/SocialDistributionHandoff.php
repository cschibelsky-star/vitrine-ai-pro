<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use DomainException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SocialDistributionHandoff
{
    /**
     * Prepare a side-effect-free distribution envelope for an already approved
     * social asset. Publication and paid media remain explicit, separate steps.
     *
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $distribution
     * @param array<string, mixed> $qa
     * @param array<string, mixed> $asset
     * @return array<string, mixed>
     */
    public function prepare(array $campaign, array $distribution, array $qa, array $asset): array
    {
        $campaignId = trim((string) ($campaign['campaign_id'] ?? ''));
        if ($campaignId === '') {
            throw new DomainException('Campaign id is required for distribution handoff.');
        }

        if (($qa['result'] ?? null) !== 'approved') {
            throw new DomainException('QA approval is required before distribution handoff.');
        }

        $versionId = trim((string) ($asset['version_id'] ?? ''));
        if ($versionId === '') {
            throw new DomainException('Approved asset version is required for distribution handoff.');
        }

        $channels = $this->approvedChannels($distribution);
        if ($channels === []) {
            throw new DomainException('At least one supported social channel is required.');
        }

        $idempotencyKey = hash('sha256', implode('|', [
            $campaignId,
            $versionId,
            implode(',', $channels),
            (string) ($qa['qa_report_id'] ?? 'qa-approved'),
        ]));

        return [
            'handoff_id' => 'HANDOFF-'.strtoupper(substr($idempotencyKey, 0, 16)),
            'campaign_id' => $campaignId,
            'distribution_plan_id' => $distribution['distribution_plan_id'] ?? null,
            'approval' => [
                'qa_report_id' => $qa['qa_report_id'] ?? null,
                'result' => 'approved',
            ],
            'asset' => [
                'request_id' => $asset['request_id'] ?? null,
                'version_id' => $versionId,
                'type' => $asset['type'] ?? 'video',
            ],
            'organic' => [
                'provider' => 'metricool',
                'channels' => $channels,
                'mode' => 'review_required',
                'auto_publish' => false,
                'scheduled' => false,
                'published' => false,
                'status' => 'ready_for_review',
            ],
            'paid_media' => [
                'provider' => 'meta_ads',
                'action' => 'none',
                'spent' => false,
                'status' => 'separate_approval_required',
            ],
            'idempotency_key' => $idempotencyKey,
            'status' => 'ready_for_review',
        ];
    }

    /**
     * Execute an explicitly requested organic publication through Meta Graph.
     * Never marks an item as published without a provider confirmation/id.
     *
     * @param array<string, mixed> $piece
     * @return array<string, mixed>
     */
    public function publishMetaNow(array $piece): array
    {
        $config = (array) config('marketing_agents.publisher.meta', []);
        $token = trim((string) ($config['access_token'] ?? ''));
        $igUserId = trim((string) ($config['instagram_user_id'] ?? ''));
        $pageId = trim((string) ($config['facebook_page_id'] ?? ''));
        $graphVersion = trim((string) ($config['graph_version'] ?? ''));
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/');

        if ($token === '' || $graphVersion === '') {
            return [
                'ok' => false,
                'status' => 'PUBLISHER_NOT_CONNECTED',
                'error' => 'meta_publisher_not_configured',
            ];
        }

        $assetUrl = trim((string) ($piece['asset_url'] ?? ''));
        if ($assetUrl === '' || filter_var($assetUrl, FILTER_VALIDATE_URL) === false || ! str_starts_with(strtolower($assetUrl), 'https://')) {
            return [
                'ok' => false,
                'status' => 'MEDIA_NOT_PUBLIC',
                'error' => 'public_https_asset_required',
            ];
        }

        $caption = trim((string) ($piece['caption'] ?? data_get($piece, 'director_job.caption', '')));
        $type = strtolower(trim((string) ($piece['type'] ?? 'image')));
        $containerId = trim((string) ($piece['publication_container_id'] ?? ''));

        if ($igUserId !== '') {
            if ($containerId === '') {
                $payload = [
                    'caption' => $caption,
                    'access_token' => $token,
                ];
                if ($type === 'video') {
                    $payload['media_type'] = 'REELS';
                    $payload['video_url'] = $assetUrl;
                } else {
                    $payload['image_url'] = $assetUrl;
                }

                $create = Http::asForm()
                    ->acceptJson()
                    ->timeout(45)
                    ->post($baseUrl.'/'.$graphVersion.'/'.$igUserId.'/media', $payload);

                if (! $create->successful() || trim((string) $create->json('id')) === '') {
                    throw new RuntimeException('Meta Instagram container error HTTP '.$create->status().'.');
                }

                $containerId = trim((string) $create->json('id'));

                if ($type === 'video') {
                    return [
                        'ok' => true,
                        'status' => 'PUBLISHING',
                        'channel' => 'instagram',
                        'container_id' => $containerId,
                        'external_id' => null,
                    ];
                }
            }

            $publish = Http::asForm()
                ->acceptJson()
                ->timeout(45)
                ->post($baseUrl.'/'.$graphVersion.'/'.$igUserId.'/media_publish', [
                    'creation_id' => $containerId,
                    'access_token' => $token,
                ]);

            if ($publish->successful() && trim((string) $publish->json('id')) !== '') {
                return [
                    'ok' => true,
                    'status' => 'PUBLISHED',
                    'channel' => 'instagram',
                    'container_id' => $containerId,
                    'external_id' => trim((string) $publish->json('id')),
                ];
            }

            if ($type === 'video') {
                return [
                    'ok' => true,
                    'status' => 'PUBLISHING',
                    'channel' => 'instagram',
                    'container_id' => $containerId,
                    'external_id' => null,
                ];
            }

            throw new RuntimeException('Meta Instagram publish error HTTP '.$publish->status().'.');
        }

        if ($pageId !== '') {
            $endpoint = $type === 'video' ? 'videos' : 'photos';
            $payload = [
                'access_token' => $token,
                'published' => 'true',
            ];
            if ($type === 'video') {
                $payload['file_url'] = $assetUrl;
                $payload['description'] = $caption;
            } else {
                $payload['url'] = $assetUrl;
                $payload['caption'] = $caption;
            }

            $publish = Http::asForm()
                ->acceptJson()
                ->timeout(60)
                ->post($baseUrl.'/'.$graphVersion.'/'.$pageId.'/'.$endpoint, $payload);

            if ($publish->successful() && trim((string) $publish->json('id')) !== '') {
                return [
                    'ok' => true,
                    'status' => 'PUBLISHED',
                    'channel' => 'facebook',
                    'container_id' => null,
                    'external_id' => trim((string) $publish->json('id')),
                ];
            }

            throw new RuntimeException('Meta Facebook publish error HTTP '.$publish->status().'.');
        }

        return [
            'ok' => false,
            'status' => 'PUBLISHER_NOT_CONNECTED',
            'error' => 'meta_account_id_not_configured',
        ];
    }

    /** @param array<string, mixed> $distribution */
    private function approvedChannels(array $distribution): array
    {
        $supported = ['facebook', 'instagram'];
        $channels = [];

        foreach ((array) ($distribution['calendar'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $channel = strtolower(trim((string) ($entry['channel'] ?? '')));
            if (in_array($channel, $supported, true) && ! in_array($channel, $channels, true)) {
                $channels[] = $channel;
            }
        }

        foreach ((array) ($distribution['channel_adaptations'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $channel = strtolower(trim((string) ($entry['channel'] ?? '')));
            if (in_array($channel, $supported, true) && ! in_array($channel, $channels, true)) {
                $channels[] = $channel;
            }
        }

        sort($channels);

        return $channels;
    }
}
