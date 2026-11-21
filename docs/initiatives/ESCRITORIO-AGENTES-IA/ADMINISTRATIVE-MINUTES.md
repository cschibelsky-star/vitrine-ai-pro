# Fluxo administrativo: ata de reunião

## Contrato de entrada

O gestor envia `meeting_title`, `notes`, listas opcionais de `decisions` e `open_items`, `priority` e uma `idempotency_key` UUID.

## Comportamento do MVP

- O pedido é autorizado somente para usuário ativo com perfil administrador.
- A missão é persistida em `office_missions` no domínio ECO, com `metadata.department=administrativo` e `metadata.workflow=meeting_minutes`.
- O resultado nasce com `review_status=awaiting_manager_review` e status operacional `planned`.
- O evento `minutes_requested` registra o pedido e a mesma chave impede duplicação.
- O funcionário IA pode registrar um resultado supervisionado com texto, pendências e nome do agente; o evento `minutes_result_submitted` devolve a missão à revisão do gestor.
- Cada novo resultado recebe uma versão; quando há pedido de ajustes, a versão anterior é preservada em `metadata.result_history`.
- O gestor continua responsável pela revisão; a tela HML permite aprovar ou solicitar ajustes. Nenhuma chamada para n8n, Avello, VIA ou Agent Hub é feita.

## Fora deste incremento

Geração automática do texto, seleção persistida de um agente do catálogo e integrações externas exigem contratos e etapas próprias.
