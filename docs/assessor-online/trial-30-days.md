# Assessor Online — avaliação experimental de 30 dias

> **AVISO HISTÓRICO — política substituída em 22/10/2026.** Este documento registra o escopo experimental anterior e não deve ser utilizado para implementar ou divulgar uma avaliação gratuita de 30 dias. A política comercial vigente está em [`commercial-launch-gates-20261022.md`](commercial-launch-gates-20261022.md): **trial gratuito de 15 dias**, sem consumo de provedores pagos; **plano de 30 dias exclusivamente pré-pago**, com preço proposto de R$ 249 e sem renovação automática. Os gates e as pendências técnicas descritos abaixo são registros históricos e não comprovam homologação atual. G0–G7 permanecem sujeitos a validação antes de vendas ou produção.


## Auditoria 13/10/2026
PR #108 aberto/draft, head 61ca813ea5c0491e6d7d45089c3460f7a5356ddd. Landing PHP e demonstração estática Rafael Monteiro/Vila Esperança preservadas. Nginx demonstrativo serve somente HTML; NÃO executa Laravel nem oferece API de cadastro.

Super Centro Operacional 0.3.14 respondeu saudável (97 projetos). Consulta project_list_files para assessor-online respondeu com repository /srv/projects/assessor-online/repository. V5 confirmou checkout limpo, mas quatro commits atrás da branch remota. O registro do projeto existe agora: a observação anterior de ausência em hml-gates.md é histórica.

hml_route_inspect(route_id=assessor-online) retornou route_not_registered. Isso não prova ausência de uma rota com outro ID; nenhum ID canônico foi obtido. Probe HTTPS de assessor.hml.vitrineiapro.com.br/health falhou SSLCertVerificationError. Não confundir esse endereço candidato com domínio homologado. Nenhuma configuração DNS/TLS foi alterada.

## Componentes implementados
Cadastro experimental para usuário já autenticado, ativo, e-mail verificado e vinculado à company canônica. O company_id vem da sessão, nunca do formulário. Não cria usuários reais nem empresas arbitrárias. Formulário separado, CSRF Laravel, throttle, escape Blade, cache privado/no-store, gate HML e flag falsa por padrão. Rotas Laravel em /assessor/avaliacao: este caminho ainda precisa de proxy para o runtime PHP, pois o Nginx estático não o atende.

Um pedido por company, inclusive depois de cancelado, com UUID aleatório de 128 bits, nome cifrado usando APP_KEY, consentimento versionado e eventos sem dados pessoais. Não cria contratos, pagamentos, assinaturas ou cartões. Consentimento desta avaliação não autoriza marketing, dados de cidadãos ou comunicações eleitorais.

Provisionamento por comando interno `php artisan assessor:trial provision ID`, sem endpoint público de callback. Contrato HmlTrialProvider recebe tenant/company/hostname; retorna evidências tipadas de DNS, TLS, health, isolamento, Cockpit e acesso. Início em UTC somente após todos os gates; término exatamente 30 dias depois. Repetição não reinicia relógio. Falhas não expõem mensagens do provedor. Adapter default bloqueia explicitamente: não há URL/API inventada.

Cancelamento bloqueia o status imediatamente e solicita revogação idempotente. Falha deixa cancelled_pending para reconciliação. Expiração nega acesso na hora exata independentemente do scheduler; comando expire reconcilia expiração e revogação. Nenhuma exclusão destrutiva é automática. Cada aplicação de gabinete DEVE aplicar AssessorTrialAccess e escopo company em todas as consultas, inclusive jobs, storage e exportações. O middleware não constitui um backend parlamentar completo.

## Contrato proposto ao provedor oficial (ainda não integrado)
- provision(tenantId, companyId, hostname): idempotente pelo tenantId, timeout limitado, runtime/data/storage/secrets isolados e acesso inicialmente negado.
- hostname: ao-<32 hex>.hml.vitrineiapro.com.br, gerado pelo servidor, uma só etiqueta abaixo de hml. Sem nomes pessoais, domínio arbitrário ou upstream vindo do usuário.
- receipt: tenant_id string, company_id int, hostname exato, environment=hml, route_id não vazio; dns_valid/tls_valid/healthy/isolated/cockpit_registered/access_ready estritamente booleanos true.
- Provedor deve atestar acesso autenticado e aplicar revogação/expiração, antes de access_ready; não publicar runtime parcial.
- revoke(tenantId): revoga acesso no proxy e runtime, idempotente, não apaga dados. Falhas mantêm pendência rastreável.
- Dados recebidos não incluem nome do gabinete, e-mail ou dados de cidadãos.
- Cockpit: projeto assessor-online, build SHA testado, ambiente hml, URL validada, route_id, estado real de health/integração, isolamento e responsável. Recibo não substitui read-back no Cockpit.

Ferramentas disponíveis hml_route_provision(project_id,upstream,friendly,health_path) e hml_route_activate(route_id) são operações de rota; não comprovam criação de tenant, banco, storage, credenciais ou registro Cockpit por gabinete. Não foram acionadas para evitar expor um serviço incompleto. A necessidade de upstream confiável deve ser resolvida no adapter, sem reutilizar o serviço de demonstração como aplicativo.

## Bloqueadores para provisionamento funcional
1. Adapter oficial server-side, contrato autenticação/timeout/idempotência/read-back e revogação por tenant.
2. Runtime Laravel HML dedicado e banco/storage/secrets isolados; checkout HML atualizado por fluxo oficial após preservação. Migração incluída, não executada.
3. ID canônico de rota, DNS e certificado válidos; confirmar wildcard *.hml.vitrineiapro.com.br ou emitir certificados individuais. Domínio final comercial e subdomínios de produção não são ativados por este PR.
4. Registro/read-back Cockpit de cada ambiente, com build e health comprovados; não foi possível confirmar esse registro nesta auditoria.
5. Fluxo de onboarding e verificação de responsável/gabinete no Core e e-mail, RBAC definitivo, limites de recursos e isolamento de dados testado no aplicativo real. Não habilitar autocadastro anônimo antes disso.
6. Scheduler de `assessor:trial expire` e worker/reconciliação, teste de revogação no proxy e rollback.
7. Aviso de privacidade definitivo, retenção/exportação e atendimento a titulares homologados antes de dados reais.
8. UI servida/mobile e integração ponta a ponta com provedor real ainda não homologadas.

## Validação e ativação
Workflow assessor-trial.yml executa contratos independentes, lint e testes Laravel de persistência/ciclo de vida em SQLite. Nenhum teste cria gabinete real ou chama infraestrutura externa. Habilitar ASSESSOR_TRIAL_HML_ENABLED somente em APP_ENV=hml após estes gates. A demonstração e landing comerciais não prometem que a avaliação está disponível.
