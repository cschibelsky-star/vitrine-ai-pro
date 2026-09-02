<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

class ViaSignalDetector
{
    public function detect(array $snapshot): array
    {
        $status = (string) ($snapshot['status'] ?? 'normal');
        if ($status === 'normal') {
            return [];
        }

        $source = (string) ($snapshot['source'] ?? 'unknown');
        $projectId = $snapshot['project_id'] ?? null;
        $domain = (string) ($snapshot['domain'] ?? 'unknown');

        $signal = match ($source) {
            'ai_budget' => $this->budgetSignal($snapshot),
            'factory_context' => $this->factorySignal($snapshot),
            'ecosystem.system' => $this->systemSignal($snapshot),
            'ecosystem.docker' => $this->dockerSignal($snapshot),
            'ecosystem.project' => $this->projectSignal($snapshot),
            'ecosystem.connectors' => $this->connectorsSignal($snapshot),
            'ecosystem.service' => $this->ecosystemServiceSignal($snapshot),
            'ecosystem.publication' => $this->ecosystemPublicationSignal($snapshot),
            'ecosystem.summary' => $this->ecosystemSummarySignal($snapshot),
            'ecosystem.source' => $this->ecosystemSourceSignal($snapshot),
            default => [
                'type' => 'operational_attention',
                'severity' => $status,
                'confidence' => 0.8,
                'title' => 'Atenção operacional detectada',
                'description' => 'O Sentinel detectou um estado diferente do baseline normal.',
                'evidence' => (array) ($snapshot['evidence'] ?? []),
            ],
        };

        $signal['domain'] = $domain;
        $signal['project_id'] = $projectId;
        $signal['source'] = $source;
        $signal['fingerprint'] = hash('sha256', implode('|', [
            (string) $signal['type'],
            $domain,
            (string) $projectId,
            $source,
        ]));

        return [$signal];
    }

    private function budgetSignal(array $snapshot): array
    {
        $percent = data_get($snapshot, 'metrics.usage_percent');
        $severity = (string) ($snapshot['status'] ?? 'attention');

        return [
            'type' => 'ai_budget_threshold',
            'severity' => $severity,
            'confidence' => 1.0,
            'title' => $severity === 'alert' ? 'Orçamento de IA próximo do limite' : 'Consumo de IA requer atenção',
            'description' => $percent === null
                ? 'O Sentinel detectou uma condição de atenção no orçamento de IA.'
                : sprintf('O consumo mensal de IA atingiu %.2f%% do limite configurado.', (float) $percent),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function factorySignal(array $snapshot): array
    {
        $severity = (string) ($snapshot['status'] ?? 'attention');
        $exitCode = data_get($snapshot, 'metrics.latest_report_exit_code');
        $schema = (array) data_get($snapshot, 'evidence.schema_assessment', []);

        if ($exitCode !== null && (int) $exitCode !== 0) {
            return [
                'type' => 'factory_report_failed',
                'severity' => 'alert',
                'confidence' => 1.0,
                'title' => 'Falha detectada em relatório da Factory',
                'description' => 'O relatório mais recente da Factory possui exit code diferente de zero.',
                'evidence' => (array) ($snapshot['evidence'] ?? []),
            ];
        }

        return [
            'type' => 'factory_schema_attention',
            'severity' => $severity,
            'confidence' => 0.95,
            'title' => 'Estrutura da Factory requer atenção',
            'description' => ! empty($schema['historical_record_may_predate_current_schema'])
                ? 'O registro mais recente pode ter sido criado antes do schema atual e deve ser tratado como evidência histórica.'
                : 'O Sentinel detectou uma condição de atenção na Factory.',
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function systemSignal(array $snapshot): array
    {
        $severity = (string) ($snapshot['status'] ?? 'attention');
        return [
            'type' => 'system_capacity_threshold',
            'severity' => $severity,
            'confidence' => 1.0,
            'title' => 'Capacidade da VPS requer atenção',
            'description' => sprintf(
                'CPU %.1f%%, memória %.1f%% e disco %.1f%%.',
                (float) data_get($snapshot, 'metrics.cpu_percent', 0),
                (float) data_get($snapshot, 'metrics.memory_percent', 0),
                (float) data_get($snapshot, 'metrics.disk_percent', 0),
            ),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function dockerSignal(array $snapshot): array
    {
        $severity = (string) ($snapshot['status'] ?? 'attention');
        return [
            'type' => 'docker_runtime_attention',
            'severity' => $severity,
            'confidence' => 1.0,
            'title' => $severity === 'critical' ? 'Serviço crítico Docker indisponível' : 'Docker requer atenção operacional',
            'description' => sprintf(
                'Falhas: %d, unhealthy: %d, reiniciando: %d.',
                (int) data_get($snapshot, 'metrics.failed', 0),
                (int) data_get($snapshot, 'metrics.unhealthy', 0),
                (int) data_get($snapshot, 'metrics.restarting', 0),
            ),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function projectSignal(array $snapshot): array
    {
        $severity = (string) ($snapshot['status'] ?? 'attention');
        $projectId = (string) ($snapshot['project_id'] ?? 'projeto');
        $dirty = (bool) data_get($snapshot, 'metrics.git_dirty', false);
        $failedServices = (int) data_get($snapshot, 'metrics.failed_services', 0);

        if ($failedServices > 0) {
            $title = 'Projeto com serviço(s) falhando';
            $description = sprintf('%s possui %d serviço(s) com falha.', $projectId, $failedServices);
        } elseif ($dirty) {
            $title = 'Projeto com alterações Git não consolidadas';
            $description = sprintf('%s possui alterações locais; preserve o estado antes de intervenções estruturais.', $projectId);
        } else {
            $title = 'Projeto requer atenção';
            $description = sprintf('%s apresenta estado fora do baseline normal.', $projectId);
        }

        return [
            'type' => $dirty && $failedServices === 0 ? 'project_git_dirty' : 'project_runtime_attention',
            'severity' => $severity,
            'confidence' => 0.95,
            'title' => $title,
            'description' => $description,
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function connectorsSignal(array $snapshot): array
    {
        $unavailable = (int) data_get($snapshot, 'metrics.unavailable', 0);
        return [
            'type' => 'connector_availability',
            'severity' => (string) ($snapshot['status'] ?? 'attention'),
            'confidence' => 1.0,
            'title' => 'Conector(es) indisponível(is)',
            'description' => sprintf('%d conector(es) do ecossistema estão indisponíveis.', $unavailable),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function ecosystemServiceSignal(array $snapshot): array
    {
        $service = (string) ($snapshot['project_id'] ?? 'serviço');
        $severity = (string) ($snapshot['status'] ?? 'attention');
        $status = (string) data_get($snapshot, 'evidence.service.status', 'unknown');
        $error = (string) data_get($snapshot, 'evidence.service.error', '');

        return [
            'type' => 'ecosystem_service_attention',
            'severity' => $severity,
            'confidence' => 1.0,
            'title' => sprintf('%s requer atenção', strtoupper($service)),
            'description' => $error !== ''
                ? sprintf('%s está em estado %s: %s.', $service, $status, $error)
                : sprintf('%s está em estado %s no ecossistema.', $service, $status),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function ecosystemPublicationSignal(array $snapshot): array
    {
        $publicationId = (string) ($snapshot['project_id'] ?? 'publicação');
        $severity = (string) ($snapshot['status'] ?? 'attention');
        $domain = (string) data_get($snapshot, 'evidence.publication.domain', $publicationId);
        $httpStatus = data_get($snapshot, 'metrics.http_status');
        $dnsOk = (bool) data_get($snapshot, 'metrics.dns_ok', false);
        $tlsOk = (bool) data_get($snapshot, 'metrics.tls_ok', false);
        $sslDays = data_get($snapshot, 'metrics.ssl_days_remaining');
        $issues = (array) data_get($snapshot, 'evidence.issues', []);

        $reason = 'A publicação está fora do baseline normal.';
        if (! $dnsOk) {
            $reason = 'O domínio não resolveu corretamente no DNS.';
        } elseif (! $tlsOk) {
            $reason = 'A conexão TLS/SSL da publicação falhou.';
        } elseif ($httpStatus !== null && (int) $httpStatus >= 500) {
            $reason = sprintf('A publicação respondeu HTTP %d.', (int) $httpStatus);
        } elseif ($sslDays !== null && (int) $sslDays < 14) {
            $reason = sprintf('O certificado SSL expira em aproximadamente %d dia(s).', (int) $sslDays);
        } elseif ($issues !== []) {
            $reason = 'O monitor funcional registrou uma ou mais ocorrências na publicação.';
        }

        return [
            'type' => 'publication_unavailable',
            'severity' => $severity,
            'confidence' => 1.0,
            'title' => sprintf('%s requer atenção', $domain),
            'description' => $reason,
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function ecosystemSummarySignal(array $snapshot): array
    {
        return [
            'type' => 'ecosystem_summary_attention',
            'severity' => (string) ($snapshot['status'] ?? 'attention'),
            'confidence' => 1.0,
            'title' => 'Ecossistema requer atenção',
            'description' => sprintf(
                '%d de %d serviços estão online; %d degradado(s) e %d offline.',
                (int) data_get($snapshot, 'metrics.online', 0),
                (int) data_get($snapshot, 'metrics.total', 0),
                (int) data_get($snapshot, 'metrics.degraded', 0),
                (int) data_get($snapshot, 'metrics.offline', 0),
            ),
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }

    private function ecosystemSourceSignal(array $snapshot): array
    {
        $reason = (string) data_get($snapshot, 'evidence.reason', 'unknown');
        return [
            'type' => 'ecosystem_observer_unavailable',
            'severity' => 'attention',
            'confidence' => 1.0,
            'title' => 'Fonte agregada do ecossistema indisponível',
            'description' => 'O Sentinel não conseguiu consultar a fonte agregada VAE do ecossistema: '.$reason.'.',
            'evidence' => (array) ($snapshot['evidence'] ?? []),
        ];
    }
}
