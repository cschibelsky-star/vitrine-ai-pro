<?php

/**
 * O QUE É: o serviço de decisão da VIA — a camada supervisora do Escritório.
 * COMO FUNCIONA: recebe a decisão, valida a INDEPENDÊNCIA de quem decide e
 * registra o julgamento com rastro de auditoria.
 * FUNÇÃO NO SISTEMA: ⭐⭐ ser a única porta de entrada para a aprovação ou a
 * devolução de uma entrega de agente.
 *
 * ⭐⭐⭐ A REGRA QUE ESTE SERVIÇO GARANTE:
 *
 *   "Nenhum agente passa o serviço para o outro sem a supervisão da VIA aprovar.
 *    Caso não seja aprovado, volta ao agente novamente até a aprovação."
 *
 *   ⭐⭐ E O DETALHE QUE A AUDITORIA APONTOU, E QUE AQUI É IMPLEMENTADO:
 *
 *   ⭐⭐⭐ QUEM PRODUZ NÃO PODE JULGAR.
 *
 *   A auditoria estava certa: os testes de reflexão provam que a porta de
 *   decisão não está na assinatura do executor — mas isso NÃO prova que a decisão
 *   veio de um supervisor AUTENTICADO e INDEPENDENTE do trabalho julgado. ⭐ Um
 *   admin que produz a entrega e a aprova no mesmo clique passaria por todos os
 *   testes existentes.
 *
 *   ⭐⭐ AQUI O CONTRATO É OUTRO: a decisão exige um `via_supervisor_id` — o
 *   supervisor que responde pela supervisão — e ele é:
 *   - ⭐ OBRIGATÓRIO (não pode vir vazio);
 *   - ⭐ ⭐ DIFERENTE de quem produziu a entrega (`submitted_by`), quando há
 *     entrega;
 *   - ⭐ DIFERENTE de quem criou a missão (`created_by`), quando há missão
 *     sem entrega registrada.
 *
 *   ⭐⭐ E O EFEITO: a supervisão deixa de ser decorativa. ⭐ Uma IA (ou uma
 *   pessoa que produziu o trabalho) não consegue aprovar o próprio trabalho.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use App\Models\OfficeMission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ViaDecisionService
{
    /**
     * ⭐⭐ REGISTRA A DECISÃO DA VIA.
     *
     * @param  bool  $aprovado  A VIA aprovou?
     * @param  string|null  $parecer  Por que aprovou ou por que reprovou.
     * @param  int  $supervisorId  ⭐ QUEM decide — o supervisor da VIA.
     * @param  int|null  $atorId  Quem está executando a requisição (para a auditoria).
     */
    public function decide(
        OfficeMission $mission,
        bool $aprovado,
        ?string $parecer,
        int $supervisorId,
        ?int $atorId = null,
    ): OfficeMission {
        return DB::transaction(function () use ($mission, $aprovado, $parecer, $supervisorId, $atorId): OfficeMission {
            $mission = OfficeMission::query()->lockForUpdate()->findOrFail($mission->getKey());

            $metadata = $mission->metadata ?? [];

            // ⭐⭐ A VIA SÓ DECIDE O QUE ESTÁ EM REVISÃO.
            //
            // ⭐ `abort_unless` com 409 — o mesmo padrão que o
            // `AdministrativeMinutesService` já usava neste fluxo. ⭐ 409 é
            // "conflito com o estado atual": ⭐ a entrega já foi julgada, ou o
            // supervisor não pode julgar.
            abort_unless(
                in_array($metadata['review_status'] ?? null, ['awaiting_manager_review', 'changes_requested'], true),
                409,
                'A entrega não está em revisão da VIA.',
            );

            // ⭐⭐⭐ A INDEPENDÊNCIA DO SUPERVISOR.
            //
            // ⭐⭐⭐ É AQUI QUE A SUPERVISÃO DEIXA DE SER DECORATIVA.
            //
            // ⭐ Quem produziu a entrega não pode julgá-la: se pudesse, a VIA
            // seria um carimbo — e a regra inteira do Escritório seria uma
            // formalidade.
            //
            // ⭐ E o mesmo vale para quem CRIOU a missão: quem define o trabalho
            // não pode ser o único a approve o próprio escopo.
            $produtor = $metadata['result_submitted_by'] ?? null;

            // ⭐ 403 — "falta permissão". ⭐ É a recusa de AUTORIZAÇÃO, e não
            // conflito de estado: ⭐ o supervisor existe e tem permissão geral,
            // mas não pode julgar ESTE trabalho.
            if ($produtor !== null && (int) $produtor === $supervisorId) {
                abort(
                    403,
                    'A VIA não pode ser o supervisor de uma entrega que o próprio supervisor produziu.',
                );
            }

            $criador = $mission->created_by;

            if ($produtor === null && (int) $criador === $supervisorId) {
                abort(
                    403,
                    'A VIA não pode ser o supervisor da própria missão que criou.',
                );
            }

            $fromStatus = $mission->status;

            // ⭐⭐ APROVAR vs REPROVAR.
            //
            // ⭐ Aprovado: a entrega é liberada — e fica com a VERSÃO que foi
            // julgada, para que ninguém troque o conteúdo depois da aprovação.
            //
            // ⭐ Reprovado: ⭐ a tarefa VOLTA AO MESMO AGENTE, na próxima
            // rodada. ⭐ O responsável não muda — é a regra do Escritório: "volta
            // ao AGENTE novamente até a aprovação".
            $toStatus = $aprovado ? 'completed' : 'planned';

            $metadata['review_status'] = $aprovado ? 'approved' : 'changes_requested';
            $metadata['review_note'] = $parecer;
            $metadata['reviewed_by'] = $supervisorId;

            // ⭐⭐ QUEM JULGOU — para a auditoria saber que houve VIA, e não só
            // uma atualização de status.
            $metadata['via_supervisor_id'] = $supervisorId;

            if ($aprovado) {
                $metadata['approved_result_version'] = (int) ($metadata['result_version'] ?? 0);
            }

            // ⭐⭐ A RODADA — quantas vezes a entrega voltou. ⭐ É o que torna
            // visível o "até a aprovação" da regra.
            if (! $aprovado) {
                $metadata['revision_round'] = (int) ($metadata['revision_round'] ?? 1) + 1;
            }

            $mission->update(['status' => $toStatus, 'metadata' => $metadata]);

            // ⭐⭐ A TRILHA DE AUDITORIA — com ator e supervisor.
            $mission->events()->create([
                'actor_id' => $atorId ?? $supervisorId,
                'event_type' => $aprovado ? 'via_approved' : 'via_changes_requested',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'request_id' => (string) Str::uuid(),
                'payload' => [
                    'supervisor_id' => $supervisorId,
                    'producer_id' => $produtor,
                    'note' => $parecer,
                    'approved_result_version' => $metadata['approved_result_version'] ?? null,
                    'revision_round' => $metadata['revision_round'] ?? 1,
                    'handoff_released' => $aprovado,
                ],
            ]);

            return $mission->refresh();
        });
    }
}
