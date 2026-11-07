# Auditoria e reestruturacao da plataforma comercial — 2026-11-07

Status: proposta de arquitetura, sem implantacao. Fonte de verdade: GitHub. Escopo: portal comercial e integracao com Core; produtos permanecem independentes.

## Evidencias verificadas na branch main
- `produtos.php`: catalogo estatico de seis solucoes, CTAs para paginas especificas e diagnostico. Contem etiqueta "White Label"; revisar contra politica comercial atual (sem white label).
- `guia-digital.php`: planos Start R$ 497, Pro R$ 997 e Governo R$ 1.500 por mes codificados no HTML/PHP. Valores sao referencias existentes, nao tabela aprovada para novas vendas.
- `routes/asaas.php`: endpoint POST /api/asaas/webhook e GET de diagnostico; nao comprova autenticidade do webhook, idempotencia ou ativacao.
- `README_PATCH_1_3.txt`: documenta POST /api/leads, throttle 60/min e retorno 201; documentacao historica, nao prova funcionamento atual.
- `app/Filament/Widgets/ExecutiveStatsWidget.php`: contadores de Lead, Company, Contract, License e Module; nao demonstra conciliacao de estados.
- `vitrine-ai-social-enterprise/app/Data/LaunchLeadData.php`: captura de origem e UTMs em produto separado; avaliar integracao sem duplicar titularidade do lead.

## Arquitetura alvo
Portal publico -> API comercial versionada no Core -> catalogo/planos -> lead/oportunidade -> proposta ou pedido -> pagamento (quando aplicavel) -> verificacao -> contrato/licenca -> provisionamento especifico do produto.

Responsabilidades:
- Portal: apresentacao, SEO, formularios e consentimento; sem credenciais financeiras.
- Core: IDs canonicos, catalogo aprovado, preco vigente, CRM, contratos, pagamentos e licencas.
- Produtos: provisionamento isolado, status operacional e callbacks autenticados; nao manter CRM paralelo como fonte de verdade.
- Asaas: webhook validado e idempotente, conciliacao antes de qualquer liberacao.
- Governo: funil consultivo e proposta formal; nao impor checkout automatico.

## Contratos propostos (nao implementados)
- GET /api/commercial/v1/catalog: produto, status_comercial, publico, slug, planos aprovados e versao.
- POST /api/commercial/v1/leads: email, contato, consentimento, origem, UTMs, produto_interesse e chave de idempotencia.
- POST /api/commercial/v1/opportunities: lead_id, product_id, plan_id opcional, modalidade consultiva/self-service.
- POST /api/commercial/v1/orders: opportunity_id, plan_version, preco congelado e idempotency_key; somente autenticado.
- GET /api/commercial/v1/orders/{id}: estado filtrado por autorizacao do titular.

Estados sugeridos: lead_novo -> qualificado -> proposta -> aprovado -> aguardando_pagamento -> pago_verificado -> provisionamento_pendente -> ativo. Rejeitado/cancelado/erro sao estados explicitos e auditaveis. Nunca ativar por callback nao verificado.

## Riscos e controles
1. Precos e nomes estaticos divergentes: inventariar e aprovar antes de centralizar.
2. White Label na vitrine publica: corrigir somente apos validacao editorial.
3. Leads duplicados: chave canonica, normalizacao e regras de mesclagem revisaveis.
4. Webhook financeiro: assinatura/token, replay, idempotencia, log sem segredos, conciliacao.
5. LGPD: finalidade, consentimento quando aplicavel, minimizacao, retencao e controle de acesso.
6. Regressao HML/producao: testes de rotas e screenshots de comparacao; nenhum deploy automatico.
7. Produtos experimentais: status visivel (disponivel, piloto, em desenvolvimento) e CTA adequado.

## Sequencia de PRs
1. Inventario completo de rotas, formularios, controladores, migrations, testes, planos, links e ambientes.
2. Contratos e testes de catalogo/lead, com mocks e sem alterar APIs antigas.
3. Camada de apresentacao e componentes de paginas, preservando URLs e redirecionamentos.
4. Integracao CRM e oportunidades com migracao aditiva, sem executar migrations em producao.
5. Checkout, webhook e conciliacao em sandbox; provisionamento apenas apos verificacao.
6. Observabilidade, eventos de funil, SEO, acessibilidade e testes E2E HML.

## Criterios de aceite
- Catalogo exibe apenas ofertas aprovadas; URLs existentes continuam funcionando.
- Cada lead recebe identificador canonico, origem e trilha de auditoria.
- Duplicidade de envio e webhook nao gera dupla cobranca nem dupla ativacao.
- Pagamento nao verificado nunca ativa licenca.
- Fluxo consultivo funciona sem checkout.
- Testes de unidade, integracao, regressao visual e seguranca passam em HML.
- Revisao humana e autorizacao separada antes de merge, migration aplicada ou producao.

## HANDOFF
Realizado: consulta GitHub, leitura dos arquivos listados e documento de arquitetura.
Nao realizado: execucao de testes, verificacao de runtime, deploy, alteracao de preco, migrations, credenciais ou chamadas pagas.
Proximo passo: inventario de codigo e verificacao dos PRs/CI para priorizar primeira implementacao isolada.
