# Vitrine IA Pro — VPS MCP Connector

Conector MCP remoto para permitir auditoria operacional da VPS pela IA.

## Estado atual

Primeira versão em **modo somente leitura**. Não possui ferramentas de deploy, edição, reinício, exclusão, migração ou execução arbitrária de shell.

## Ferramentas expostas

- `system_health`
- `docker_status`
- `git_status`
- `laravel_status`
- `queue_status`
- `read_laravel_log`
- `factory_inventory`
- `audit_snapshot`

## Regra de segurança

**Não publicar diretamente na internet ainda.**

Antes da conexão com o ChatGPT, o serviço precisa receber:

1. autenticação OAuth compatível com MCP/ChatGPT;
2. proxy HTTPS dedicado;
3. rate limiting;
4. auditoria de chamadas;
5. política de IP e bloqueio por origem;
6. revisão do acesso ao Docker socket.

O socket Docker concede poder elevado no host mesmo quando o código só declara operações de leitura. A versão de produção deverá usar um broker de comandos com allowlist, em vez de entregar o socket diretamente ao serviço público.

## Arquitetura aprovada

```text
ChatGPT
  ↓ OAuth + HTTPS
mcp.vitrineiapro.com.br
  ↓
MCP Gateway
  ↓ allowlist somente leitura
VPS Operations Broker
  ├── sistema
  ├── Docker
  ├── Git
  ├── Laravel
  ├── filas
  ├── scheduler
  └── logs permitidos
```

## Fases

### Fase 1 — Auditoria somente leitura

Consulta de estado e diagnóstico. Nenhuma alteração permitida.

### Fase 2 — Ações controladas

Ações como limpar cache, reiniciar worker ou executar deploy deverão exigir confirmação explícita, trilha de auditoria e rollback.

### Fase 3 — Operação assistida

Deploy, homologação e rollback integrados à Factory, sempre com escopos e aprovações.

## Execução local de desenvolvimento

```bash
cd services/vps-mcp-connector
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python server.py
```

O endpoint SSE ficará disponível em `http://127.0.0.1:8765/sse/` quando o host for limitado a localhost.
