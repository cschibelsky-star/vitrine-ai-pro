#!/usr/bin/env bash
set -Eeuo pipefail

INSTALL_DIR="/srv/connectors/vitrine-vps-mcp"
BRANCH="feature/vps-mcp-connector-readonly"
BASE_URL="https://raw.githubusercontent.com/cschibelsky-star/vitrine-ai-pro/${BRANCH}/services/vps-mcp-connector"

echo "[1/5] Preparando diretório isolado..."
mkdir -p "$INSTALL_DIR"
chmod 750 "$INSTALL_DIR"
cd "$INSTALL_DIR"

echo "[2/5] Baixando arquivos versionados..."
for file in server.py requirements.txt Dockerfile docker-compose.mcp.yml; do
  curl -fsSL "$BASE_URL/$file" -o "$file"
done

echo "[3/5] Validando Docker..."
docker version >/dev/null
docker compose version >/dev/null

echo "[4/5] Construindo e iniciando o conector..."
docker compose -f docker-compose.mcp.yml up -d --build

echo "[5/5] Verificando saúde..."
sleep 8
docker compose -f docker-compose.mcp.yml ps

if docker inspect --format '{{.State.Health.Status}}' vitrine_vps_mcp_connector 2>/dev/null | grep -q healthy; then
  echo
  echo "CONECTOR MCP INSTALADO E SAUDÁVEL"
  echo "Endpoint privado local: http://127.0.0.1:8765/mcp"
  echo "O endpoint NÃO foi publicado na internet."
else
  echo
  echo "O container iniciou, mas ainda não ficou saudável."
  echo "Execute: docker logs --tail 100 vitrine_vps_mcp_connector"
  exit 1
fi
