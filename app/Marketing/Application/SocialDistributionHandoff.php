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
    public function publishMetaNow(array $piece, array $account = []): array
    {
        $config = array_replace((array) config('marketing_agents.publisher.meta', []), $account);
        $token = trim((string) ($config['access_token'] ?? ''));
        $igUserId = trim((string) ($config['instagram_user_id'] ?? ''));
        $pageId = trim((string) ($config['facebook_page_id'] ?? $config['page_id'] ?? ''));
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
                    $ready = false;
                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        $status = Http::acceptJson()
                            ->timeout(20)
                            ->get($baseUrl.'/'.$graphVersion.'/'.$containerId, [
                                'fields' => 'status_code',
                                'access_token' => $token,
                            ]);

                        $statusCode = strtoupper(trim((string) $status->json('status_code')));
                        if ($status->successful() && $statusCode === 'FINISHED') {
                            $ready = true;
                            break;
                        }
                        if (in_array($statusCode, ['ERROR', 'EXPIRED'], true)) {
                            throw new RuntimeException('Meta Instagram video container failed with status '.$statusCode.'.');
                        }
                        usleep(1500000);
                    }

                    if (! $ready) {
                        return [
                            'ok' => true,
                            'status' => 'PUBLISHING',
                            'channel' => 'instagram',
                            'container_id' => $containerId,
                            'external_id' => null,
                        ];
                    }
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

    /**
     * Publish a TV Sumare article to Instagram using the article metadata.
     * The article page remains the source of truth; only title, summary and
     * a public HTTPS image are sent to Instagram.
     *
     * @return array<string, mixed>
     */
    public function publishTvSumareArticleNow(string $articleUrl, string $format = 'image'): array
    {
        $articleUrl = trim($articleUrl);
        if ($articleUrl === '' || filter_var($articleUrl, FILTER_VALIDATE_URL) === false) {
            throw new DomainException('A valid article URL is required.');
        }

        $parts = parse_url($articleUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($host, ['tvsumare.com.br', 'www.tvsumare.com.br'], true)) {
            throw new DomainException('Only TV Sumare article URLs are allowed.');
        }
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            throw new DomainException('TV Sumare article URL must use HTTPS.');
        }

        $response = Http::accept('text/html')->timeout(30)->get($articleUrl);
        if (! $response->successful()) {
            throw new RuntimeException('TV Sumare article fetch error HTTP '.$response->status().'.');
        }

        $html = (string) $response->body();
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('Unable to parse TV Sumare article HTML.');
        }

        $xpath = new \DOMXPath($dom);
        $meta = static function (\DOMXPath $xpath, string $property): string {
            $queries = [
                "//meta[@property='{$property}']/@content",
                "//meta[@name='{$property}']/@content",
            ];
            foreach ($queries as $query) {
                $nodes = $xpath->query($query);
                if ($nodes !== false && $nodes->length > 0) {
                    $value = trim((string) $nodes->item(0)?->nodeValue);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
            return '';
        };

        $title = $meta($xpath, 'og:title');
        if ($title === '') {
            $nodes = $xpath->query('//title');
            $title = $nodes !== false && $nodes->length > 0
                ? trim((string) $nodes->item(0)?->textContent)
                : '';
        }

        $summary = $meta($xpath, 'og:description');
        if ($summary === '') {
            $summary = $meta($xpath, 'description');
        }

        $imageUrl = $meta($xpath, 'og:image');
        if ($imageUrl === '' || filter_var($imageUrl, FILTER_VALIDATE_URL) === false || ! str_starts_with(strtolower($imageUrl), 'https://')) {
            throw new RuntimeException('TV Sumare article has no public HTTPS og:image.');
        }

        if ($title === '') {
            throw new RuntimeException('TV Sumare article title was not found.');
        }

        $captionParts = [$title];
        if ($summary !== '') {
            $captionParts[] = $summary;
        }
        $captionParts[] = 'Leia a materia completa: '.$articleUrl;
        $captionParts[] = '#TVSumare #Sumare';

        $format = strtolower(trim($format));
        if (! in_array($format, ['image', 'reel'], true)) {
            throw new DomainException('Unsupported TV Sumare publication format.');
        }

        if ($format === 'reel') {
            $slug = 'tv-sumare-'.substr(hash('sha256', $articleUrl), 0, 20);
            $reel = app(VideoFinalizationService::class)->createTvSumareArticleReel(
                $slug,
                $imageUrl,
                $title,
                $summary,
            );
            $assetUrl = route('marketing.media.tv-sumare-reel', ['slug' => $reel['slug']]);
            $publication = $this->publishMetaNow([
                'type' => 'video',
                'asset_url' => $assetUrl,
                'caption' => implode("\n\n", $captionParts),
            ]);
        } else {
            $publication = $this->publishMetaNow([
                'type' => 'image',
                'asset_url' => $imageUrl,
                'caption' => implode("\n\n", $captionParts),
            ]);
        }

        return array_merge($publication, [
            'article_url' => $articleUrl,
            'article_title' => $title,
            'article_summary' => $summary,
            'article_image_url' => $imageUrl,
            'publication_format' => $format,
            'publication_asset_url' => $assetUrl ?? $imageUrl,
        ]);
    }

    /**
     * Fetch provider-confirmed publication metadata and basic engagement.
     * This is intentionally limited to fields that can be queried directly
     * from the published object without inferring reach or paid-media results.
     *
     * @param array<string, mixed> $piece
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    public function fetchMetaPublicationMetrics(array $piece, array $account = []): array
    {
        $config = array_replace((array) config('marketing_agents.publisher.meta', []), $account);
        $token = trim((string) ($config['access_token'] ?? ''));
        $graphVersion = trim((string) ($config['graph_version'] ?? ''));
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/');
        $externalId = trim((string) ($piece['publication_external_id'] ?? ''));
        $channel = strtolower(trim((string) ($piece['publication_channel'] ?? '')));

        if ($token === '' || $graphVersion === '' || $externalId === '') {
            return [
                'ok' => false,
                'status' => 'METRICS_UNAVAILABLE',
                'error' => 'publisher_or_external_id_missing',
            ];
        }

        $fields = $channel === 'instagram'
            ? 'id,timestamp,permalink,media_type,media_product_type,like_count,comments_count'
            : 'id,created_time,permalink_url';

        $response = Http::acceptJson()
            ->timeout(30)
            ->get($baseUrl.'/'.$graphVersion.'/'.$externalId, [
                'fields' => $fields,
                'access_token' => $token,
            ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'status' => 'METRICS_RETRY',
                'http_status' => $response->status(),
                'error' => 'meta_metrics_http_error',
            ];
        }

        $payload = (array) $response->json();

        return [
            'ok' => true,
            'status' => 'METRICS_SYNCED',
            'channel' => $channel,
            'external_id' => $externalId,
            'provider_data' => [
                'timestamp' => $payload['timestamp'] ?? $payload['created_time'] ?? null,
                'permalink' => $payload['permalink'] ?? $payload['permalink_url'] ?? null,
                'media_type' => $payload['media_type'] ?? null,
                'media_product_type' => $payload['media_product_type'] ?? null,
            ],
            'engagement' => [
                'likes' => isset($payload['like_count']) ? (int) $payload['like_count'] : null,
                'comments' => isset($payload['comments_count']) ? (int) $payload['comments_count'] : null,
            ],
            'synced_at' => now()->toISOString(),
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
