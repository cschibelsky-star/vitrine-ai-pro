<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use Illuminate\Support\Facades\Http;

class ViaEcosystemContextCollector
{
    public function collect(): array
    {
        if (! (bool) config('via_sentinel.ecosystem_enabled', false)) {
            return [
                'enabled' => false,
                'available' => false,
                'status' => 'normal',
                'reason' => 'ecosystem_disabled',
                'snapshots' => [],
            ];
        }

        $url = trim((string) config('via_sentinel.ecosystem_url', ''));
        if ($url === '') {
            return [
                'enabled' => true,
                'available' => false,
                'status' => 'attention',
                'reason' => 'ecosystem_url_not_configured',
                'snapshots' => [],
            ];
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('via_sentinel.ecosystem_timeout_seconds', 6))
                ->get(rtrim($url, '/'));
        } catch (\Throwable $e) {
            report($e);

            return [
                'enabled' => true,
                'available' => false,
                'status' => 'attention',
                'reason' => 'ecosystem_unreachable',
                'snapshots' => [],
            ];
        }

        $payload = $response->json();
        if (! $response->successful() || ! is_array($payload)) {
            return [
                'enabled' => true,
                'available' => false,
                'status' => 'attention',
                'reason' => 'ecosystem_invalid_response',
                'http_status' => $response->status(),
                'snapshots' => [],
            ];
        }

        $snapshots = $this->normalize($payload);

        return [
            'enabled' => true,
            'available' => true,
            'status' => $snapshots === [] ? 'attention' : 'normal',
            'reason' => $snapshots === [] ? 'ecosystem_payload_without_supported_signals' : null,
            'snapshots' => $snapshots,
        ];
    }

    private function normalize(array $payload): array
    {
        $vaeServices = data_get($payload, 'ecosystem.services');
        if (! is_array($vaeServices)) {
            $vaeServices = data_get($payload, 'services');
        }

        if (is_array($vaeServices)) {
            return $this->normalizeVae($payload, $vaeServices);
        }

        return $this->normalizeSupervisor($payload);
    }

    private function normalizeVae(array $payload, array $services): array
    {
        $snapshots = [];

        foreach ($services as $service) {
            if (! is_array($service)) {
                continue;
            }

            $id = strtolower((string) data_get($service, 'id', 'unknown'));
            $status = strtolower((string) data_get($service, 'status', 'unknown'));
            $critical = (bool) data_get($service, 'critical', false);
            $metrics = (array) data_get($service, 'metrics', []);
            $issues = (array) data_get($service, 'issues', []);

            if ($id === 'docker') {
                $unhealthy = (int) ($this->number(data_get($metrics, 'containersUnhealthy')) ?? 0);
                $restarting = (int) ($this->number(data_get($metrics, 'containersRestarting')) ?? 0);
                $stopped = (int) ($this->number(data_get($metrics, 'containersStopped')) ?? 0);

                // Containers parados, isoladamente, não representam incidente: o ecossistema
                // possui jobs one-shot (migrate/seed/check) que terminam normalmente.
                // Só elevamos severidade com sinal operacional explícito.
                $dockerStatus = match (true) {
                    $unhealthy > 0 => 'alert',
                    $restarting > 0 => 'attention',
                    $stopped > 0 => 'info',
                    $status === 'online', $status === 'healthy', $status === 'ok' => 'normal',
                    default => 'info',
                };

                $snapshots[] = [
                    'domain' => 'infrastructure',
                    'source' => 'ecosystem.docker',
                    'project_id' => null,
                    'status' => $dockerStatus,
                    'metrics' => [
                        'running' => $this->number(data_get($metrics, 'containersRunning')),
                        'total' => $this->number(data_get($metrics, 'containersTotal')),
                        'healthy' => $this->number(data_get($metrics, 'containersHealthy')),
                        'unhealthy' => $unhealthy,
                        'restarting' => $restarting,
                        'stopped' => $stopped,
                        'images' => $this->number(data_get($metrics, 'images')),
                        'memory_bytes' => $this->number(data_get($metrics, 'memoryBytes')),
                        'cpus' => $this->number(data_get($metrics, 'cpus')),
                    ],
                    'evidence' => [
                        'service' => $service,
                        'issues' => $issues,
                        'classification' => [
                            'stopped_containers_are_incident_by_themselves' => false,
                            'requires_unhealthy_or_restarting_signal_for_attention' => true,
                            'one_shot_jobs_may_exit_normally' => true,
                        ],
                    ],
                ];
                continue;
            }

            if ($id === 'factory') {
                $gitClean = data_get($metrics, 'gitClean');
                $factoryStatus = $this->serviceStatus($status, $critical);
                if ($factoryStatus === 'normal' && $gitClean === false) {
                    $factoryStatus = 'info';
                }

                $snapshots[] = [
                    'domain' => 'projects',
                    'source' => 'ecosystem.project',
                    'project_id' => 'factory',
                    'status' => $factoryStatus,
                    'metrics' => [
                        'healthy' => $status === 'online',
                        'running' => $status === 'online',
                        'git_dirty' => $gitClean === false,
                        'changed_files' => $this->number(data_get($metrics, 'changedFiles')),
                        'tracked_changes' => $this->number(data_get($metrics, 'trackedChanges')),
                        'untracked_files' => $this->number(data_get($metrics, 'untrackedFiles')),
                    ],
                    'evidence' => ['service' => $service],
                ];
                continue;
            }

            if ($id === 'publications') {
                $publications = (array) data_get($service, 'publications', []);
                foreach ($publications as $publication) {
                    if (! is_array($publication)) {
                        continue;
                    }

                    $publicationId = strtolower((string) data_get($publication, 'id', data_get($publication, 'domain', 'publication')));
                    $publicationStatus = strtolower((string) data_get($publication, 'status', 'unknown'));
                    $publicationCritical = (bool) data_get($publication, 'critical', false);
                    $publicationIssues = (array) data_get($publication, 'issues', []);

                    $snapshots[] = [
                        'domain' => 'publications',
                        'source' => 'ecosystem.publication',
                        'project_id' => $publicationId,
                        'status' => $this->serviceStatus($publicationStatus, $publicationCritical),
                        'metrics' => [
                            'critical' => $publicationCritical,
                            'dns_ok' => (bool) data_get($publication, 'dns.ok', false),
                            'tls_ok' => (bool) data_get($publication, 'tls.ok', false),
                            'ssl_days_remaining' => $this->number(data_get($publication, 'tls.daysRemaining')),
                            'http_ok' => (bool) data_get($publication, 'http.ok', false),
                            'http_status' => $this->number(data_get($publication, 'http.statusCode')),
                            'http_latency_ms' => $this->number(data_get($publication, 'http.latencyMs')),
                        ],
                        'evidence' => [
                            'publication' => $publication,
                            'issues' => $publicationIssues,
                        ],
                    ];
                }
                continue;
            }

            $snapshots[] = [
                'domain' => in_array($id, ['github', 'n8n'], true) ? 'connectors' : 'services',
                'source' => 'ecosystem.service',
                'project_id' => $id,
                'status' => $this->serviceStatus($status, $critical),
                'metrics' => array_merge($metrics, [
                    'latency_ms' => $this->number(data_get($service, 'latencyMs')),
                ]),
                'evidence' => [
                    'service' => $service,
                    'issues' => $issues,
                ],
            ];
        }

        $summary = (array) data_get($payload, 'ecosystem.summary', data_get($payload, 'summary', []));
        if ($summary !== []) {
            $snapshots[] = [
                'domain' => 'ecosystem',
                'source' => 'ecosystem.summary',
                'project_id' => null,
                'status' => $this->summaryStatus((string) data_get($payload, 'ecosystem.status', data_get($payload, 'status', 'normal'))),
                'metrics' => $summary,
                'evidence' => ['summary' => $summary],
            ];
        }

        return $snapshots;
    }

    private function normalizeSupervisor(array $payload): array
    {
        $snapshots = [];

        $system = (array) data_get($payload, 'system', data_get($payload, 'health.system', []));
        if ($system !== []) {
            $diskPercent = $this->number(data_get($system, 'disk.percent'));
            $memoryPercent = $this->number(data_get($system, 'memory.percent'));
            $cpuPercent = $this->number(data_get($system, 'cpu_percent'));

            $snapshots[] = [
                'domain' => 'infrastructure',
                'source' => 'ecosystem.system',
                'project_id' => null,
                'status' => $this->systemStatus($cpuPercent, $memoryPercent, $diskPercent),
                'metrics' => [
                    'cpu_percent' => $cpuPercent,
                    'memory_percent' => $memoryPercent,
                    'disk_percent' => $diskPercent,
                    'disk_free_bytes' => $this->number(data_get($system, 'disk.free')),
                ],
                'evidence' => ['system' => $system],
            ];
        }

        return $snapshots;
    }

    private function serviceStatus(string $status, bool $critical): string
    {
        return match ($status) {
            'online', 'healthy', 'ok' => 'normal',
            'degraded', 'warning', 'attention' => 'attention',
            'offline', 'failed', 'error', 'unhealthy' => $critical ? 'critical' : 'attention',
            default => 'attention',
        };
    }

    private function summaryStatus(string $status): string
    {
        return match (strtolower($status)) {
            'online', 'healthy', 'ok', 'normal' => 'normal',
            'attention', 'degraded', 'warning' => 'attention',
            'critical' => 'critical',
            default => 'alert',
        };
    }

    private function systemStatus(?float $cpu, ?float $memory, ?float $disk): string
    {
        $max = max($cpu ?? 0, $memory ?? 0, $disk ?? 0);
        if (($disk ?? 0) >= 95 || ($cpu ?? 0) >= 95 || ($memory ?? 0) >= 95) return 'critical';
        if (($disk ?? 0) >= 90 || ($cpu ?? 0) >= 85 || ($memory ?? 0) >= 85) return 'alert';
        if (($disk ?? 0) >= 80 || ($cpu ?? 0) >= 75 || ($memory ?? 0) >= 75) return 'attention';
        if (($disk ?? 0) >= 70 || $max >= 65) return 'info';
        return 'normal';
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
