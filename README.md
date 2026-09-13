# VITRINE AI PRO ENTERPRISE 6.0 RC1

## Inclui

- Centro Operacional 6.0
- Factory Studio 2.0
- Projetos Gerados
- Marketplace
- Comercial → Factory Pipeline
- Ocultação de módulos gerados no menu principal
- Bootstrap único

## Instalação

Extraia na raiz do projeto e rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 vitrine_enterprise_6_rc1_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "commercial:factory"
```

## Teste

```bash
php artisan commercial:factory-intake "TV Digital Enterprise" "Cliente Teste TV" --plan=enterprise --email=cliente@teste.com --domain=cliente.tv.br --dry-run
php artisan commercial:factory-status
```

## Vitrine Flow Foundation

O n8n e a camada oficial de automacao e integracao entre sistemas da Vitrine IA Pro.

### Fronteiras de responsabilidade

- APP: regras de negocio, persistencia principal e processamento interno do dominio.
- FLOW: webhooks, integracoes entre sistemas, schedules de negocio, retries, notificacoes e aprovacoes.
- AI: VIA, Gemini, agentes, Veo e demais capacidades de inteligencia.
- OPS: Super Centro Operacional, V5, VPS Operations e V4 de contingencia para Git, Docker, deploy, runtime e infraestrutura.

### Convencao de workflows

`VITRINE / <AMBIENTE> / <DOMINIO> / <PROCESSO> / V<MAJOR>`

Exemplos:

- `VITRINE / HML / MARKETING / CONTENT_PIPELINE / V1`
- `VITRINE / HML / SOCIAL / PUBLISH_CONTENT / V1`
- `VITRINE / HML / FACTORY / INTAKE_PIPELINE / V1`
- `VITRINE / HML / SYSTEM / ERROR_HANDLER / V1`

### Contrato minimo de evento

Todo evento entre APP, AI e FLOW deve carregar:

- `event_id`
- `event_type`
- `tenant_id`
- `project_id`
- `execution_id`
- `source`
- `occurred_at`
- `payload`

### Regras obrigatorias

1. Workflows com efeitos externos devem ser idempotentes por `event_id`.
2. Integracoes externas devem usar retry finito e encaminhar falhas persistentes ao error handler central.
3. HML e PROD usam workflows e credenciais separados.
4. Secrets nunca sao exportados para arquivos versionados nem lidos de outros projetos pelo n8n.
5. GitHub e a fonte de verdade dos contratos e dos exports de workflows.
6. n8n nao executa Git, Docker, deploy, migrations, leitura de `.env` ou administracao da VPS.
7. Jobs internos continuam nas filas da aplicacao; orquestracao entre sistemas pertence ao Flow.
8. Nenhum scheduler existente deve ser removido antes de classificar suas tarefas em APP ou FLOW.

### Ordem de implantacao

1. Foundation e governanca.
2. Piloto Marketing -> Social -> Drive -> Metricool.
3. VIA Action Layer -> Flow.
4. Factory Intake -> Radar -> Matching -> Action Engine.
5. TV Sumare e Cursos IA.
