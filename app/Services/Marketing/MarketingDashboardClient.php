<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Http;
use Throwable;

final class MarketingDashboardClient
{
    /** @return array<string, mixed> */
    public function fetch(): array
    {
        $token = (string) config('centro_ia.internal_token', '');
        $url = (string) env(
            'MARKETING_INTERNAL_DASHBOARD_URL',
            'http://marketing_web_internal/api/internal/marketing/dashboard-state',
        );

        if ($token === '') {
            return $this->unavailable('internal_token_not_configured');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(5)
                ->get($url);

            if (! $response->successful()) {
                return $this->unavailable('marketing_service_http_'.$response->status());
            }

            $payload = $response->json();

            if (! is_array($payload) || ! ($payload['ok'] ?? false)) {
                return $this->unavailable('invalid_marketing_service_response');
            }

            return [
                ...$payload,
                'available' => true,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailable('marketing_service_unreachable');
        }
    }

    /** @return array<string, mixed> */
    private function unavailable(string $reason): array
    {
        return [
            'ok' => false,
            'available' => false,
            'reason' => $reason,
            'runtime' => [],
            'agents' => [],
            'pipeline' => [],
            'state' => [
                'available' => false,
                'campaign' => null,
                'tasks' => [],
                'status_counts' => [],
                'artifact_count' => null,
                'blocked_tasks' => [],
            ],
        ];
    }
}
