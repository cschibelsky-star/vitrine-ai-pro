<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use DomainException;

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
