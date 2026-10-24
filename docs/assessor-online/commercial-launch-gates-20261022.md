# Assessor Online — contrato funcional e gates de lançamento (22/10/2026)

Status: APROVADO COMO ESCOPO COMERCIAL; IMPLEMENTAÇÃO E HOMOLOGAÇÃO PENDENTES. Este arquivo não habilita cobranças, trial ou produção.

## Produtos e vigência
- Trial: 15 dias por conta, mediante cadastro e confirmação de e-mail; sem cobrança e sem consumo de IA, SMS, WhatsApp ou outros provedores pagos.
- Assessor Online 30 dias: R$ 249 por período pré-pago, sem renovação automática; franquias e custos variáveis com limite rígido, a definir antes de ativar.
- Assessor Online 12 meses: preço proposto R$ 2.190, pagamento anual, sem renovação automática; página pessoal padrão incluída durante a vigência, sem custo adicional de criação/hospedagem básica.
- Página pessoal avulsa: mensalidade recorrente proposta R$ 79,90, autorização expressa de cobrança e cancelamento; sem taxa de criação padrão. O site pode ser contratado isoladamente ou continuar após o término do plano anual.
- Preços são propostas aceitas para avaliação, sujeitos a validação de margem, tributos, meios de pagamento e aprovação final antes de cobrança real.

## Entitlements e ciclo de vida
- Core é a fonte de verdade de cliente, tenant, lead, pagamento, plano, prazo, franquia e estado de assinatura.
- Estados separados: trial, active_prepaid_30d, active_annual, active_site_only, expired, suspended, canceled; idempotência na ativação por pagamento confirmado; sem ativação por callback não verificado.
- Página vinculada ao período anual, e mantida mediante renovação anual ou assinatura site-only. Não cobrar site-only simultaneamente ao anual para o mesmo site.
- No vencimento sem pagamento, despublicar após aviso/prazo contratual, preservar conteúdo por período contratual a definir, permitir reativação e exportação. Não prometer retenção indefinida.
- Centro IA controla roteamento, créditos, franquias, hard caps e auditoria; VIA não controla orçamento.
- Isolamento multi-tenant e RBAC: gabinete A não acessa gabinete B; cidadão não acessa painel administrativo.

## Página pessoal e domínio
- Modelo padrão reproduzível e responsivo: biografia, foto, agenda, projetos, notícias, contato; painel editorial.
- Subdomínio de plataforma provisionado apenas quando a assinatura do site estiver ativa.
- Domínio próprio opcional e de titularidade do cliente: comprovar controle, instruir DNS, validar HTTPS, vincular host ao tenant correto e permitir troca sem perda de conteúdo.
- Registro/renovação de domínio próprio por conta do cliente; não transferir propriedade para Vitrine IA Pro.
- Suspender hospedagem por inadimplência não cancela o registro do domínio próprio do cliente.
- Respeitar LGPD e regras de comunicação institucional/eleitoral; publicação editorial sob aprovação do cliente.

## Gates de homologação antes de vendas
G0: Git limpo, ambiente HML isolado, rollback e backup comprovados.
G1: login, RBAC, tenants e persistência real (sem dados simulados).
G2: cidadãos, protocolos, agenda, documentos, relatórios e fluxos ponta a ponta.
G3: PWA cidadão -> protocolo -> gabinete -> resposta, com testes de isolamento.
G4: trial de 15 dias, pagamento verificado, acesso 30 dias e anual, vencimento, renovação manual e franquias com hard caps.
G5: site padrão, site-only recorrente, renovação/cancelamento, migração anual -> site-only sem perda, domínio próprio DNS/TLS.
G6: landing de vendas, comparação de preços concorrentes verificáveis e datados, LGPD, descadastro de marketing e CRM de leads.
G7: testes funcionais e de segurança, smoke desktop/mobile, aceite humano e autorização expressa de publicação em produção.

## Estado técnico verificado em 22/10/2026
- HML /demo.html HTTP 200; /health HTTP 200.
- HML /login, /admin e /api/health HTTP 404 nos caminhos testados; não prova inexistência em outros serviços.
- hml/assessor/demo.html é demonstração estática com dados simulados. Não equivale a sistema integrado e homologado.
- Nenhum gate G1-G7 declarado concluído. Não habilitar contratação real nem disparos em massa.
