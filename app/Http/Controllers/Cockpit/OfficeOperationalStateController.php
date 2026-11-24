<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cockpit;

use App\Marketing\Infrastructure\Persistence\Models\MarketingAuditEvent;
use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaignTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class OfficeOperationalStateController
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('app.env') === 'homologation', 404);

        $user = $request->user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $companyId = $user->company_id ? (int) $user->company_id : null;
        $tasks = collect();
        $events = collect();

        if (Schema::hasTable('marketing_campaign_tasks')) {
            $tasksQuery = MarketingCampaignTask::query()
                ->with('agentDefinition')
                ->latest('updated_at');
            if ($companyId !== null) {
                $tasksQuery->where('company_id', $companyId);
            }
            $tasks = $tasksQuery->limit(100)->get()->unique('agent_definition_id')->values();
        }

        if (Schema::hasTable('marketing_audit_events')) {
            $eventsQuery = MarketingAuditEvent::query()->latest('created_at');
            if ($companyId !== null) {
                $eventsQuery->where('company_id', $companyId);
            }
            $events = $eventsQuery->limit(20)->get();
        }

        $taskByAgent = $tasks->keyBy(fn ($task): string => (string) ($task->agentDefinition?->agent_key ?? ''));
        $agents = collect(config('marketing_agents.agents', []))->map(
            function (array $definition, string $agentId) use ($taskByAgent): array {
                $task = $taskByAgent->get($agentId);
                $status = match ((string) ($task?->status ?? '')) {
                    'running' => 'working',
                    'awaiting_via_review' => 'awaiting_via_review',
                    'needs_revision' => 'needs_revision',
                    'ready_for_handoff' => 'ready_for_handoff',
                    'failed' => 'failed',
                    default => $task ? 'idle' : 'idle',
                };

                return [
                    'id' => $agentId,
                    'department' => $this->departmentFor($agentId),
                    'name' => (string) ($definition['name'] ?? $agentId),
                    'role' => (string) ($definition['type'] ?? 'specialist'),
                    'status' => $status,
                    'task_id' => $task?->getKey(),
                    'heartbeat_at' => $task?->updated_at?->toIso8601String(),
                    'artifact_version' => null,
                ];
            }
        )->values();

        $gateTask = $tasks->firstWhere('via_status', 'awaiting_review')
            ?? $tasks->first(fn ($task): bool => in_array((string) $task->via_status, ['approved', 'changes_requested'], true));

        $gate = [
            'status' => (string) ($gateTask?->via_status ?? 'not_submitted'),
            'task_id' => $gateTask?->getKey(),
            'agent_id' => $gateTask?->agentDefinition?->agent_key,
            'version' => null,
            'reviewed_by' => $gateTask?->via_reviewed_by,
            'reviewed_at' => $gateTask?->via_reviewed_at?->toIso8601String(),
            'note' => $gateTask?->via_note,
        ];

        return response()->json([
            'snapshot_at' => now()->toIso8601String(),
            'product' => $request->string('product', 'vitrine_social_midia')->toString(),
            'campaign_id' => $gateTask?->campaign_id,
            'departments' => [
                ['key' => 'strategy', 'name' => 'Estratégia', 'order' => 1],
                ['key' => 'intelligence', 'name' => 'Inteligência', 'order' => 2],
                ['key' => 'creation', 'name' => 'Criação', 'order' => 3],
                ['key' => 'distribution', 'name' => 'Distribuição', 'order' => 4],
            ],
            'agents' => $agents,
            'pipeline' => $this->pipelineFor($gate['status']),
            'gate' => $gate,
            'activity' => $events->map(fn ($event): array => [
                'id' => (string) $event->getKey(),
                'event_type' => (string) $event->event_type,
                'occurred_at' => $event->created_at?->toIso8601String(),
                'message' => (string) $event->event_type,
                'actor_id' => $event->actor_id,
            ])->values(),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function departmentFor(string $agentId): string
    {
        return match ($agentId) {
            'marketing_director', 'product_market_strategist', 'campaign_planner' => 'strategy',
            'copy_content', 'qa_brand_guardian', 'performance_analyst' => 'intelligence',
            'creative_director', 'video_producer' => 'creation',
            'social_distribution' => 'distribution',
            default => 'strategy',
        };
    }

    /** @return list<array{key:string,label:string,status:string,order:int}> */
    private function pipelineFor(string $gateStatus): array
    {
        $active = $gateStatus === 'awaiting_review' ? 'judge' : 'data';
        $steps = [
            ['key' => 'data', 'label' => 'Dados'], ['key' => 'analysis', 'label' => 'Análise'],
            ['key' => 'judge', 'label' => 'VIA'], ['key' => 'meeting', 'label' => 'Reunião'],
            ['key' => 'script', 'label' => 'Roteiro'], ['key' => 'memory', 'label' => 'Memória'],
            ['key' => 'production', 'label' => 'Produção'], ['key' => 'publication', 'label' => 'Publicação'],
            ['key' => 'approval', 'label' => 'Aprovação'],
        ];

        return collect($steps)->values()->map(function (array $step, int $index) use ($active): array {
            $order = $index + 1;
            return $step + ['status' => $step['key'] === $active ? 'active' : ($order < 3 ? 'done' : 'pending'), 'order' => $order];
        })->all();
    }
}
