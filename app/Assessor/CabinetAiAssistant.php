<?php
namespace App\Assessor;

use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetAiAssistant
{
    public function context(int $cabinet, int $actor): array
    {
        $member = DB::table('assessor_cabinet_members as members')
            ->join('assessor_cabinets as cabinets', 'cabinets.id', '=', 'members.cabinet_id')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->where('members.cabinet_id', $cabinet)->where('members.user_id', $actor)
            ->whereColumn('users.company_id', 'cabinets.company_id')
            ->whereIn('members.role', ['admin', 'assessor'])->exists();
        if (!$member) {
            throw new DomainException('cabinet_access_denied');
        }
        $name = DB::table('assessor_cabinets')->where('id', $cabinet)->value('name');
        if ($name === null) {
            throw new DomainException('cabinet_not_found');
        }
        $demands = DB::table('assessor_demands')->where('cabinet_id', $cabinet);
        $tasks = DB::table('assessor_tasks')->where('cabinet_id', $cabinet);
        return [
            'cabinet' => $name,
            'metrics' => [
                'open_demands' => (clone $demands)->where('status', 'open')->count(),
                'in_progress_demands' => (clone $demands)->where('status', 'in_progress')->count(),
                'resolved_demands' => (clone $demands)->where('status', 'resolved')->count(),
                'pending_tasks' => (clone $tasks)->where('status', 'pending')->count(),
                'overdue_tasks' => (clone $tasks)->whereNotNull('due_date')
                    ->where('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
        ];
    }

    public function recommendations(int $cabinet, int $actor, bool $generate = false): array
    {
        $context = $this->context($cabinet, $actor);
        $metrics = $context['metrics'];
        $items = [];
        if ($metrics['overdue_tasks'] > 0) {
            $items[] = ['priority' => 'high', 'title' => 'Revisar tarefas vencidas',
                'reason' => $metrics['overdue_tasks'].' tarefa(s) com prazo vencido'];
        }
        if ($metrics['open_demands'] > 0) {
            $items[] = ['priority' => 'medium', 'title' => 'Triar demandas abertas',
                'reason' => $metrics['open_demands'].' demanda(s) aguardando encaminhamento'];
        }
        if ($metrics['pending_tasks'] > 0) {
            $items[] = ['priority' => 'medium', 'title' => 'Distribuir atividades pendentes',
                'reason' => $metrics['pending_tasks'].' tarefa(s) pendente(s)'];
        }
        $fallback = ['source' => 'local_rules', 'ai_connected' => false,
            'context' => $context, 'recommendations' => $items];
        if (!$generate) {
            return $fallback;
        }
        // Authorization above must happen even when the integration is disabled.
        $result = app(CabinetCentroIaClient::class)->recommend($context['metrics']);
        \Illuminate\Support\Facades\Log::info('assessor.recommendations', [
            'cabinet_id' => $cabinet, 'actor_id' => $actor,
            'source' => $result['source'], 'execution_id' => $result['execution_id'] ?? null,
            'fallback_reason' => $result['fallback_reason'] ?? null,
        ]);
        return $result['source'] === 'centro_ia'
            ? array_merge($fallback, $result)
            : array_merge($fallback, ['fallback_reason' => $result['fallback_reason']]);
    }
}
