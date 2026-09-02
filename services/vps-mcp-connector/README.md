# Vitrine IA Pro — VPS MCP Operations Connector

Conector MCP operacional para a VPS da Vitrine IA Pro.

## Leitura

- saúde da VPS;
- Docker;
- Git;
- Laravel;
- filas e scheduler;
- logs;
- inventário da Factory.

## Operações controladas

- comandos Factory e Comercial presentes em allowlist;
- testes Laravel;
- limpeza de cache;
- reinício de containers autorizados;
- deploy de branch com backup, fast-forward, Composer, migrations e optimize.

## Segurança

- não existe shell arbitrário;
- não existe exclusão de arquivos ou banco;
- toda operação exige `confirm="EXECUTAR"`;
- deploy é recusado quando a árvore Git está suja;
- broker operacional fica apenas na rede interna Docker;
- todas as ações são registradas em JSONL;
- o MCP permanece local em `127.0.0.1:8765` até a publicação autenticada.

## Instalação ou atualização

```bash
curl -fsSL https://raw.githubusercontent.com/cschibelsky-star/vitrine-ai-pro/feature/vps-mcp-connector-readonly/services/vps-mcp-connector/install.sh | bash
```

## Serviços

- `vitrine_vps_mcp_connector` — endpoint MCP;
- `vitrine_mcp_ops_broker` — execução controlada;
- `vitrine_mcp_docker_proxy` — leitura Docker.

## Ferramentas MCP

### Diagnóstico

- `system_health`
- `docker_status`
- `git_status`
- `laravel_status`
- `queue_status`
- `read_laravel_log`
- `factory_inventory`
- `audit_snapshot`

### Operação

- `run_factory_command`
- `run_laravel_tests`
- `clear_laravel_cache`
- `restart_application_container`
- `deploy_git_branch`

## Auditoria

```text
/srv/connectors/vitrine-vps-mcp/audit/audit.jsonl
```

## Limitação atual

A instalação na VPS não conecta automaticamente este ChatGPT. Ainda é necessário disponibilizar o endpoint por HTTPS autenticado ou túnel MCP compatível e adicioná-lo como conector no ChatGPT.
