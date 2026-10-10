"""Verify that isolated deployment keeps the approved homepage Git blobs."""
import hashlib
import json
from pathlib import Path
root = Path(__file__).resolve().parents[1]
identity = json.loads((root / "deployment-source.json").read_text())
assert identity["repository"] == "cschibelsky-star/vitrine-ai-pro"
assert identity["production_enabled"] is False
for name, expected in identity["baseline_blobs"].items():
    data = (root / name).read_bytes()
    actual = hashlib.sha1(b"blob " + str(len(data)).encode() + b"\0" + data).hexdigest()
    assert actual == expected, f"Approved layout changed: {name}"
compose = (root / "docker-compose.origin-hml.yml").read_text()
assert '"127.0.0.1:18896:80"' in compose
assert 'traefik.enable: "false"' in compose
assert "internal: true" in compose
assert "Dockerfile.origin-hml" in compose
for prohibited in ["conhecasumare.com.br", "external: true", "/srv/projects/", ".env.runtime", "container_name:", "conheca_sumare_prod"]:
    assert prohibited not in compose, f"Production coupling: {prohibited}"
print("OK: approved layout identity and isolated deployment")
