<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\ViaOperationalSnapshot;
use App\Shared\AI\Models\ViaSignal;
use Illuminate\Support\Arr;

class ViaSentinelService
{
    public function __construct(
        private readonly ViaSentinelCollector $collector,
        private readonly ViaSignalDetector $detector,
    ) {
    }

    public function run(): array
    {
        if (! config('via_sentinel.enabled', true)) {
            return ['enabled' => false, 'snapshots' => 0, 'signals' => 0];
        }

        if (strtoupper((string) config('via_sentinel.mode', 'OBSERVER')) !== 'OBSERVER') {
            throw new \RuntimeException('VIA Sentinel deve operar em modo OBSERVER nesta fase.');
        }

        $snapshotCount = 0;
        $signalCount = 0;
        $seenSignalFingerprints = [];

        foreach ($this->collector->collect() as $payload) {
            $fingerprint = $this->snapshotFingerprint($payload);
            $snapshot = ViaOperationalSnapshot::create([
                'domain' => (string) ($payload['domain'] ?? 'unknown'),
                'source' => (string) ($payload['source'] ?? 'unknown'),
                'project_id' => $payload['project_id'] ?? null,
                'status' => (string) ($payload['status'] ?? 'unknown'),
                'metrics' => (array) ($payload['metrics'] ?? []),
                'evidence' => (array) ($payload['evidence'] ?? []),
                'fingerprint' => $fingerprint,
                'collected_at' => now(),
            ]);
            $snapshotCount++;

            foreach ($this->detector->detect($snapshot->toArray()) as $signalData) {
                $seenSignalFingerprints[] = (string) $signalData['fingerprint'];
                $this->upsertSignal($signalData);
                $signalCount++;
            }
        }

        $this->resolveMissingSignals($seenSignalFingerprints);
        $this->pruneOldSnapshots();

        return [
            'enabled' => true,
            'mode' => 'OBSERVER',
            'snapshots' => $snapshotCount,
            'signals' => $signalCount,
            'status' => $this->status(),
        ];
    }

    public function status(): array
    {
        $open = ViaSignal::query()->where('status', 'open')->get();
        $order = ['info' => 1, 'attention' => 2, 'alert' => 3, 'critical' => 4];
        $highest = $open->sortByDesc(fn (ViaSignal $signal): int => $order[$signal->severity] ?? 0)->first();
        $latestSnapshot = ViaOperationalSnapshot::query()->latest('collected_at')->first();
        $snapshotCount = ViaOperationalSnapshot::query()->count();
        $lastSnapshotAt = $latestSnapshot?->collected_at;
        $ageSeconds = $lastSnapshotAt ? max(0, (int) $lastSnapshotAt->diffInSeconds(now())) : null;
        $staleAfter = max(60, (int) config('via_sentinel.stale_after_seconds', 600));
        $cycleHealth = $lastSnapshotAt === null ? 'unknown' : (($ageSeconds ?? PHP_INT_MAX) > $staleAfter ? 'stale' : 'healthy');

        return [
            'state' => $highest?->severity ?? 'normal',
            'open_signals' => $open->count(),
            'highest_severity' => $highest?->severity ?? 'normal',
            'updated_at' => now()->toISOString(),
            'sentinel' => [
                'enabled' => (bool) config('via_sentinel.enabled', true),
                'mode' => strtoupper((string) config('via_sentinel.mode', 'OBSERVER')),
                'cycle_health' => $cycleHealth,
                'snapshot_count' => $snapshotCount,
                'last_snapshot_at' => $lastSnapshotAt?->toISOString(),
                'last_snapshot_age_seconds' => $ageSeconds,
                'stale_after_seconds' => $staleAfter,
                'last_snapshot_source' => $latestSnapshot?->source,
                'last_snapshot_status' => $latestSnapshot?->status,
            ],
            'latest' => $highest ? [
                'id' => $highest->id,
                'title' => $highest->title,
                'project_id' => $highest->project_id,
                'severity' => $highest->severity,
                'recommendation' => $this->recommendationFor($highest),
                'last_seen_at' => optional($highest->last_seen_at)->toISOString(),
            ] : null,
        ];
    }

    public function advisorySummary(int $limit = 3): array
    {
        $order = ['info' => 1, 'attention' => 2, 'alert' => 3, 'critical' => 4];
        $signals = ViaSignal::query()
            ->where('status', 'open')
            ->get()
            ->sortByDesc(fn (ViaSignal $signal): int => $order[$signal->severity] ?? 0)
            ->take(max(1, min(5, $limit)))
            ->values();

        if ($signals->isEmpty()) {
            return [
                'state' => 'normal',
                'answer' => 'Ambiente estável. O Sentinel não possui advertências abertas neste momento.',
                'signals' => [],
            ];
        }

        $items = $signals->map(function (ViaSignal $signal, int $index): array {
            return [
                'position' => $index + 1,
                'id' => $signal->id,
                'severity' => $signal->severity,
                'title' => $signal->title,
                'project_id' => $signal->project_id,
                'description' => $signal->description,
                'recommendation' => $this->recommendationFor($signal),
                'occurrences' => (int) $signal->occurrences,
                'last_seen_at' => optional($signal->last_seen_at)->toISOString(),
            ];
        })->all();

        $lines = ['Tenho ' . count($items) . ' ponto(s) que merecem atenção:'];
        foreach ($items as $item) {
            $project = $item['project_id'] ? ' [' . $item['project_id'] . ']' : '';
            $description = trim((string) ($item['description'] ?? ''));
            $lines[] = $item['position'] . '. ' . strtoupper((string) $item['severity']) . $project . ' — ' . $item['title'] . '. '
                . ($description !== '' ? $description . ' ' : '')
                . $item['recommendation'];
        }

        return [
            'state' => (string) ($items[0]['severity'] ?? 'normal'),
            'answer' => implode("\n", $lines),
            'signals' => $items,
        ];
    }

    private function recommendationFor(ViaSignal $signal): string
    {
        return match ($signal->type) {
            'ai_budget_threshold' => 'Recomendo revisar o consumo e priorizar modelos mais econômicos nas tarefas de baixa complexidade antes de elevar o limite.',
            'factory_report_failed' => 'Recomendo interromper novas etapas dependentes desse relatório e validar a causa da falha antes de prosseguir.',
            'factory_schema_attention' => 'Recomendo tratar o registro como histórico e validar o schema atual antes de usá-lo como base para nova decisão.',
            'docker_runtime_attention' => 'Recomendo identificar os containers afetados e validar dependências de rede e proxy antes de restart, recreate ou deploy. Se não houver falha ativa, apenas acompanhe e registre a causa.',
            'project_runtime_attention' => 'Recomendo validar os serviços com falha e o impacto funcional antes de qualquer intervenção no projeto.',
            'project_git_dirty' => 'Recomendo preservar e revisar as alterações locais antes de qualquer atualização, merge, reset ou deploy.',
            'publication_unavailable' => 'Recomendo validar DNS, TLS, upstream e resposta HTTP do domínio antes de reiniciar serviços ou alterar publicação.',
            'connector_availability' => 'Recomendo identificar quais conectores estão indisponíveis e evitar operações dependentes deles até a saúde ser restabelecida.',
            'ecosystem_service_attention' => 'Recomendo validar o serviço indicado, sua dependência imediata e o impacto operacional antes de qualquer ação corretiva.',
            'ecosystem_summary_attention' => 'Recomendo priorizar primeiro os serviços offline ou degradados que afetam rotas críticas do ecossistema.',
            'ecosystem_observer_unavailable' => 'Recomendo restabelecer a fonte agregada de observabilidade antes de tomar decisões baseadas em uma visão parcial do ecossistema.',
            default => 'Recomendo validar a evidência associada e o impacto antes de qualquer alteração no ambiente.',
        };
    }

    private function snapshotFingerprint(array $payload): string
    {
        return hash('sha256', json_encode([
            'domain' => $payload['domain'] ?? null,
            'source' => $payload['source'] ?? null,
            'project_id' => $payload['project_id'] ?? null,
            'status' => $payload['status'] ?? null,
            'metrics' => Arr::sortRecursive((array) ($payload['metrics'] ?? [])),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function upsertSignal(array $data): void
    {
        $signal = ViaSignal::query()->firstOrNew(['fingerprint' => $data['fingerprint']]);
        $isNew = ! $signal->exists;

        $signal->fill([
            'type' => $data['type'],
            'domain' => $data['domain'],
            'project_id' => $data['project_id'] ?? null,
            'source' => $data['source'],
            'severity' => $data['severity'],
            'confidence' => $data['confidence'] ?? 1.0,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'evidence' => (array) ($data['evidence'] ?? []),
            'status' => 'open',
            'resolved_at' => null,
            'first_seen_at' => $isNew ? now() : ($signal->first_seen_at ?? now()),
            'last_seen_at' => now(),
            'occurrences' => $isNew ? 1 : ((int) $signal->occurrences + 1),
        ]);
        $signal->save();
    }

    private function resolveMissingSignals(array $seenFingerprints): void
    {
        $query = ViaSignal::query()->where('status', 'open');
        if ($seenFingerprints !== []) {
            $query->whereNotIn('fingerprint', array_values(array_unique($seenFingerprints)));
        }

        $query->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    private function pruneOldSnapshots(): void
    {
        $days = max(1, (int) config('via_sentinel.snapshot_retention_days', 14));
        ViaOperationalSnapshot::query()
            ->where('collected_at', '<', now()->subDays($days))
            ->delete();
    }
}
