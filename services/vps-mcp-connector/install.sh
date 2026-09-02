#!/usr/bin/env bash
set -Eeuo pipefail

INSTALL_DIR="/srv/connectors/vitrine-vps-mcp"
BRANCH="feature/vps-mcp-connector-readonly"
BASE_URL="https://raw.githubusercontent.com/cschibelsky-star/vitrine-ai-pro/${BRANCH}/services/vps-mcp-connector"

echo "[1/6] Preparando diretório isolado..."
mkdir -p "$INSTALL_DIR/audit"
chmod 750 "$INSTALL_DIR"
chmod 700 "$INSTALL_DIR/audit"
cd "$INSTALL_DIR"

echo "[2/6] Baixando arquivos versionados..."
for file in server.py main.py ops_broker.py requirements.txt Dockerfile docker-compose.mcp.yml; do
  curl -fsSL "$BASE_URL/$file" -o "$file"
done

echo "[3/6] Criando segredo interno do broker..."
if [ ! -f .env ]; then
  TOKEN="$(openssl rand -hex 32)"
  printf 'OPS_BROKER_TOKEN=%s\n' "$TOKEN" > .env
  chmod 600 .env
fi

echo "[4/6] Validando Docker..."
docker version >/dev/null
docker compose version >/dev/null

echo "[5/6] Construindo e iniciando conector + broker..."
docker compose --env-file .env -f docker-compose.mcp.yml up -d --build

echo "[6/6] Verificando saúde..."
sleep 10
docker compose --env-file .env -f docker-compose.mcp.yml ps

MCP_HEALTH="$(docker inspect --format '{{.State.Health.Status}}' vitrine_vps_mcp_connector 2>/dev/null || true)"
BROKER_HEALTH="$(docker inspect --format '{{.State.Health.Status}}' vitrine_mcp_ops_broker 2>/dev/null || true)"

if [ "$MCP_HEALTH" = "healthy" ] && [ "$BROKER_HEALTH" = "healthy" ]; then
  echo
  echo "CONECTOR MCP OPERACIONAL INSTALADO E SAUDÁVEL"
  echo "Endpoint privado local: http://127.0.0.1:8765/mcp"
  echo "Broker interno: saudável e não publicado"
  echo "Auditoria: $INSTALL_DIR/audit/audit.jsonl"
else
  echo
  echo "A instalação iniciou, mas algum serviço ainda não ficou saudável."
  echo "MCP=$MCP_HEALTH BROKER=$BROKER_HEALTH"
  echo "Execute: docker logs --tail 100 vitrine_vps_mcp_connector"
  echo "Execute: docker logs --tail 100 vitrine_mcp_ops_broker"
  exit 1
fi
