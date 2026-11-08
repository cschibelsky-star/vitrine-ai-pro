# Marketing IA — mapa de contratos e integrações (v1)

## Fonte de verdade
| Responsável | Domínio | Contrato mínimo / limites |
| --- | --- | --- |
| Core | tenants, contas, acesso, planos | client_id/tenant_id e autorização canônica; evitar cadastro paralelo |
| Centro IA | orçamento, provedores e modelos | capability, project_id, execution_id, custos e status; sem chaves nos prompts |
| Marketing IA | briefing, arte, mídia, revisões e campanha | artifact_id, version, tenant, channel, format, asset_uri, sha256, aprovado_por, aprovado_em |
| Social Hub | conexão Meta, calendário, aprovação de envio, reconciliação | idempotency_key, client_id, content_project_id, channel, placement, version, status, external_id |
| VIA | supervisão e alerta | handoff assinado/autenticado externamente, checks, SHA e decisão; OBSERVER não autoriza avanço |

## Implementações a preservar
- Marketing Agents Core PR #65 foi mesclado: nove agentes, envelope/JSON schemas, DAG, QA de marca, persistência e auditoria.
- Marketing PR #102: publicador nativo e bridge candidata com Social, ainda draft e sem instalação HML comprovada.
- Marketing PR #150: consulta somente leitura publication-status baseada no #102; draft, não presente no runtime principal HML.
- Social PRs #9 e #12: ledger/contrato candidatos; #16 reserva transacional e #17 elegibilidade por conta; não tratar como integração final.
- Central IA não é publicador. Social controla envios externos do produto; Marketing pode publicar suas campanhas próprias somente pela capacidade autorizada e com ledger canônico compartilhado.

## Matriz de testes interprodutos (exigida antes do primeiro deploy de integração)
1. Tenant A não pode observar/alterar mídia, credenciais ou status de Tenant B.
2. Sem aprovação humana/fingerprint da versão exata: recusar agendamento e envio.
3. Timeout após possível postagem: reconciliar ID/status antes de qualquer tentativa repetida.
4. Falta de capacidades/snapshot autorizado por conta/formato: falhar fechado, sem fallback silencioso.
5. Status-only não publica nem altera conteúdo.
6. Arte é imutável após aprovação; reedição gera nova versão e reaprovação.
7. Tokens/erros de rede e 403/429: classificar de forma auditável sem revelar segredos.
8. Centro IA indisponível não aciona chamada direta não rastreada de provedor nem publica conteúdo simulado.
9. Cancelamento e reagendamento invalidam jobs obsoletos; verificar timezone America/Sao_Paulo.
10. HML, DNS/HTTPS, storage e filas independentes de produção, com backup e rollback comprovados.

## Decisão nesta etapa
Documentação e gate de engenharia somente. Não se modifica código de publicação, modelos de banco, manifestos ou ambientes. Consolidar contratos Social/Marketing antes de qualquer mutação operacional.
