# Vitrine Live Commerce IA — Discovery V1

Issue: #125. Estado: especificacao inicial; sem implantacao.

## Decisao de produto
- Vitrine Shop: modelo hibrido de afiliados e produtos proprios.
- Vitrine Live Commerce: SaaS multiempresa.
- MVP: catalogo, ofertas afiliadas, estoque proprio, roteiros e criativos com aprovacao, agenda LIVE assistida por humanos, atribuicao e indicadores.
- Avatar sintetico conduzindo LIVE nao e requisito do MVP; sujeito a permissao explicita e politica vigente.

## Evidencias do repositorio
- README.md define fronteiras APP/FLOW/AI/OPS e contrato de eventos event_id, event_type, tenant_id, project_id, execution_id, source, occurred_at e payload.
- app/Marketing/Domain/Contracts/CommonEnvelope.php implementa tenant_id/company_id e bloqueia may_publish, may_spend e may_invent_facts na V1.
- social-media.php e pagina comercial da Vitrine Social Midia, nao uma integracao comprovada do TikTok Shop.

## Contratos propostos
1. ProductSource: source_type=affiliate|owned; source_id; tenant_id; store_id; product_id; status.
2. AffiliateOffer: product_id; external_offer_id; commission_terms; attribution_ref; valid_until; last_verified_at.
3. OwnedInventory: product_id; sku; available_qty; reserved_qty; unit_cost; fulfillment_policy.
4. CommerceEvent: reutilizar envelope de eventos do README, com idempotencia por event_id.
5. CampaignApproval: tenant_id; campaign_id; requested_budget; approved_budget; approved_by; expires_at; status.
6. Conversion: modality=affiliate|owned; gross_value; fees; refund_value; recognized_revenue; currency; external_ref.
7. ContentApproval: asset_id; policy_review; synthetic_media_disclosure; approved_by; publication_permission.

## Gate de implementacao
- Inventariar tabelas, migrations, rotas, RBAC, queues e contratos existentes antes de adicionar codigo.
- Verificar APIs oficiais e autorizacoes reais de TikTok Shop, TikTok Ads e afiliados por conta/regiao.
- Criar HML isolada e testes de isolamento entre tenants.
- Idempotencia e conciliacao para pedidos, cancelamentos, comissoes e estornos.
- Nenhum segredo em Git, nenhum gasto, publicacao ou deploy sem autorizacao especifica.
- Reutilizar Centro IA e Flow; nao duplicar roteamento de modelos.

## Sequencia
G0 inventario; G1 contratos; G2 loja piloto; G3 conteudo; G4 anuncios controlados; G5 SaaS; G6 QA integrado; G7 promocao autorizada.

## Pendencias comerciais
Contas TikTok elegiveis, categorias e produtos iniciais, fornecedores, CTA, brand kit e verba de teste.

## Criterios de aceite
Testes unitarios e integrados; isolamento de dados; reconciliacao de receita por modalidade; aprovacoes auditaveis; evidencia de chamadas reais quando autorizadas; nenhuma afirmacao de conformidade sem revisao das politicas atuais.
