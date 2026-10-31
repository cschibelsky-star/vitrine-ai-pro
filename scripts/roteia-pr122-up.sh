#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
compose="docker-compose.roteia-pr122-hml.yml"
if ! command -v python3 >/dev/null || ! docker info >/dev/null 2>&1; then
  echo "BLOQUEADO: Python3 ou Docker daemon indisponivel."; exit 1
fi
if [ ! -f "$compose" ]; then echo "BLOQUEADO: Compose ausente."; exit 1; fi
network_name="centro-ia-roteia-pr122-hml_pr122_internal"
subnet="$(python3 - "$network_name" <<'PY'
import ipaddress, json, subprocess, sys
network_name=sys.argv[1]
def output(*cmd):
    p=subprocess.run(cmd, capture_output=True, text=True)
    if p.returncode: raise RuntimeError(f"command failed: {cmd[0]} {cmd[1] if len(cmd)>1 else ''}")
    return p.stdout
occupied=[]
try:
    ids=output("docker","network","ls","-q").split()
    for name in ids:
        entry=json.loads(output("docker","network","inspect",name))[0]
        for config in entry.get("IPAM",{}).get("Config",[]) or []:
            if config.get("Subnet"): occupied.append(ipaddress.ip_network(config["Subnet"],strict=False))
        if entry.get("Name")==network_name:
            existing=entry.get("IPAM",{}).get("Config",[]) or []
            for row in existing:
                if row.get("Subnet"): 
                    print(row["Subnet"])
                    sys.exit(0)
    p=subprocess.run(["ip","-j","-4","route","show","table","all"],capture_output=True,text=True)
    if p.returncode: raise RuntimeError("host IPv4 routes unavailable")
    for route in json.loads(p.stdout):
        dst=route.get("dst")
        if dst and dst != "default":
            try: occupied.append(ipaddress.ip_network(dst,strict=False))
            except ValueError: pass
    for second in [249,248,247,246,245,244,243,242,241,240]:
        for third in range(0,256):
            candidate=ipaddress.ip_network(f"10.{second}.{third}.0/27")
            if not any(candidate.overlaps(o) for o in occupied if o.version==4):
                print(candidate)
                sys.exit(0)
    raise RuntimeError("no collision-free subnet found in restricted candidate ranges")
except (RuntimeError,ValueError,KeyError) as exc:
    print(f"BLOQUEADO: {exc}",file=sys.stderr)
    sys.exit(2)
PY
)"
export PR122_HML_SUBNET="$subnet"
echo "PR122_HML_SUBNET=$PR122_HML_SUBNET"
docker compose -f "$compose" config --quiet
case "${1:-up}" in
  status)
    docker compose -f "$compose" ps
    exit 0
    ;;
  up)
    echo "Compose valido; iniciando somente a stack isolada, sem novo build nem migracoes..."
    docker compose -f "$compose" up -d --no-build
    docker compose -f "$compose" ps
    ;;
  *) echo "Uso: bash scripts/roteia-pr122-up.sh [up|status]"; exit 2 ;;
esac
