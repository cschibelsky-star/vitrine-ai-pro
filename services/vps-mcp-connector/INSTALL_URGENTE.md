# Instalação urgente — Conector MCP da VPS

## Objetivo

Subir o conector MCP da Vitrine IA Pro em modo somente leitura, isolado do Laravel principal e acessível apenas em `127.0.0.1:8765`.

## Comando único

Execute como `root` na VPS:

```bash
curl -fsSL https://raw.githubusercontent.com/cschibelsky-star/vitrine-ai-pro/feature/vps-mcp-connector-readonly/services/vps-mcp-connector/install.sh | bash
```

## Resultado esperado

```text
CONECTOR MCP INSTALADO E SAUDÁVEL
Endpoint privado local: http://127.0.0.1:8765/mcp
O endpoint NÃO foi publicado na internet.
```

## Verificação

```bash
docker ps --filter name=vitrine_vps_mcp_connector
docker logs --tail 100 vitrine_vps_mcp_connector
```

## Segurança

- Sem acesso de escrita ao projeto.
- Sem shell arbitrário como ferramenta MCP.
- Sem deploy, restart ou migrations.
- Docker acessado por proxy com endpoints limitados e `POST=0`.
- Porta publicada apenas no loopback da VPS.
- Para conectar ao ChatGPT, usar preferencialmente Secure MCP Tunnel da OpenAI; não abrir diretamente a porta 8765 na internet.
