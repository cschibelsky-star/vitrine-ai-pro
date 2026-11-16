# Arquitetura de referência — Escritório de Agentes e Habilidades IA

## Diagrama lógico
```text
[ Operador / Painel + Escritório 2D + Kanban ]
                   |
[ Gestor de Missões / Estado / Fila / Auditoria ]
            |                    |
       [HUB DEV]              [HUB ECO]
  [Agentes DEV e Skills]  [Agentes ECO e Skills]
            |                    |
     [Factory / Codex /      [Centro IA +
       Claude Code]             Produtos]
            \                    /
               [HUB-IA-001]
    [catálogo + benchmarks + seleção]
      [Invision | Roteia | Avello2]
             (contas segregadas)
                   |
 [Coletores independentes de testes/evidências]
                   |
       [VIA: supervisão autenticada]
                   |
   [Gate: avanço / correção / bloqueio]
```

O HUB é **serviço de decisão** e catálogo, não precisa ser proxy obrigatório de inferência: cada consumidor mantém protocolo adequado. Não forçar Responses API em Claude Code. Sem integração real comprovada neste estágio.

## Plano DEV versus ECO

| Controle | DEV | ECO |
|---|---|---|
| Identidade/escopo | operador, repositorio, branch, sandbox | tenant, produto, cliente, ambiente |
| Dados | código, logs técnicos sanitizados | dados de negócio, conteúdo, clientes |
| Credenciais | segregadas DEV | segregadas por produto/tenant conforme política |
| Custos | orçamento por missão/ambiente | orçamento por cliente/produto |
| Ferramentas | git, testes, patch, análise | produção de mídia, publicações/CRM etc. conforme autorização |
| Aprovação | VIA DEV + testes + gate Git | VIA ECO + revisão de negócio/editorial + autorização de ação |
| Produção | sempre bloqueada sem gate | sempre bloqueada sem gate |

**Sem acesso cruzado automático.** Benchmarks globais compartilham somente métricas agregadas sem dados sensíveis e sem promover um modelo a homologado em outro ambiente.

## Conceitos de domínio
- **Department:** setor lógico (dev/backend, QA, marketing etc.).
- **AgentProfile:** identidade lógica do agente, mandato, skills versionadas, ferramentas permitidas, supervisor e limites.
- **SkillDefinition:** capacidade reutilizável, contrato de entrada/saída, precondições, testes, evidências e versão.
- **Mission:** objetivo do operador, contexto, prioridade, orçamento, ambiente e critérios de aceite.
- **Task:** unidade idempotente com dependências, agente responsável, skill, tentativas e status.
- **ModelPolicy:** seleção auto/manual; provedores/modelos elegíveis; score fundamentado em qualidade, custo por tarefa aceita, latência e disponibilidade.
- **ExecutionEvidence:** commit SHA/diff hash, resultados dos testes, origem da execução, IDs do provedor, tempos e custo (null quando desconhecido).
- **SupervisionDecision:** decisão do supervisor independente ligada a evidence_ids, identidade verificável e policy_version.

## Ciclo de vida da missão
```text
intake -> classified -> planned -> assigned -> executing -> testing
  -> awaiting_independent_verification -> approved -> completed
                                               |
                                             rejected -> rework
```
Estados adicionais: blocked, cancelled, pending_external_verification. Nenhum estado deve passar a completed apenas por resposta textual de agente.

## Contratos mínimos (proposta, não API publicada)
```json
{
  "mission_id": "uuid",
  "environment": "DEV",
  "tenant_id": null,
  "project_id": "HUB-IA-001",
  "task_id": "uuid",
  "skill_id": "dev.python.debug",
  "skill_version": "1.0",
  "selected_model": {"provider": "invision", "model": "qwen/qwen3.8-max", "selection_mode": "auto"},
  "selection_reason": "benchmarked coding quality and estimated cost",
  "requested_budget_brl": null,
  "execution_id": null,
  "evidence_refs": [],
  "approval": "pending_external_verification"
}
```
Campos de referência, e não evidência de chamada real. Não inventar `upstream_model` nem contabilizar custo zero quando desconhecido.

## Regra de decisão por habilidades
1. Classificar objetivo e skill necessária; recusar classificação insegura/ambígua.
2. Filtrar modelos com **capacidade comprovada** no protocolo e ambiente (tools, streaming, texto, imagem etc.).
3. Aplicar políticas de risco, data boundary, orçamento, teto e credenciais; se não houver garantia de teto para teste pago, manter bloqueio.
4. Ranqueamento por custo esperado por tarefa **aceita** (não apenas por token), taxa de sucesso, latência e confiabilidade, com amostra e data; nenhuma pontuação sem evidência.
5. Selecionar modelo; opcionalmente pedir aprovação humana; executar; capturar evidências.
6. Repetir/reencaminhar somente com política de retries, consentimento e limites. Modelo manual é fixo; fallback explícito e auditado.

## Fronteiras de implementação
- No ECO, **reutilizar** `app/Services/Ai/AiRoutingService.php`, `AiExecutionService.php`, `AiMediaGenerationService.php` e `CentroIaBrokerController.php`.
- No DEV, Codex e Claude Code seguem clientes/protocolos próprios com adaptadores finos; não alterar configuração global do usuário nem o ChatGPT.
- Integrar estados/eventos ao Flow apenas após inventário de fluxos existentes; usar idempotency key + ambiente + tenant.
- VIA deve ter decisão verificável server-side: flags JSON controladas pelo agente não constituem aprovação.
- UI mapa é **projeção visual do estado**, não simulador de atividade; acessibilidade e modo lista obrigatórios.
