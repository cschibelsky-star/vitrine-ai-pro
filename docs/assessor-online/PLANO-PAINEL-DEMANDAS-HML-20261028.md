# Assessor Online — painel de demandas (escopo HML)

## Base existente
- `DemandIntakeService`: protocolo UUID, validação do cidadão por tenant, gravação transacional e evento de recebimento.
- `CaseWorkflowService`: transições controladas, registro de auditoria, autorização por membership ativa e bloqueio de transições inválidas.
- Migrations existentes: `2026_10_20_000001_create_assessor_core_tables.php` e `2026_10_22_000002_create_assessor_memberships_table.php`.

## Entrega seguinte — interface administrativa autenticada
1. Listar demandas filtradas exclusivamente pelo tenant da sessão autenticada.
2. Pesquisar por protocolo/assunto, filtrar por situação, paginar e ordenar por atualização.
3. Mostrar detalhe com cidadão, descrição, localização e linha do tempo dos eventos, respeitando a LGPD.
4. Permitir transições usando somente `CaseWorkflowService::transition`; obter `tenantId` e `actorUserId` da sessão, nunca do corpo da requisição.
5. Exigir observação para conclusão e encerramento; mostrar erros de validação sem perder dados digitados.
6. Testar isolamento entre gabinetes, papéis, transições inválidas, concorrência e auditoria.

## Restrições de entrega
- Não executar migrations nem alterar produção automaticamente.
- Não substituir a landing existente.
- Manter dados existentes, rotas e demais produtos da Vitrine IA Pro intactos.
- Homologar em ambiente isolado antes de publicar.
- Esta documentação não representa implementação ou homologação concluída.
