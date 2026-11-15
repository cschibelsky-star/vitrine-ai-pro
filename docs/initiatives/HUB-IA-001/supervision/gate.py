"""Fail-closed prototype for an isolated Codex/VIA evidence handoff.

IMPORTANT: This is not a trusted VIA authorization service. Boolean flags in JSON
must be verified by an independent trusted verifier before any operational use.
"""
import json
import sys
from pathlib import Path

ALLOWED = {"order_service.py"}

def evaluate(report: dict) -> dict:
    issues = []
    if report.get("schema_version") != "1.0":
        issues.append("schema_version invalida")
    if report.get("mission_id") != "TOOL-CODE-02":
        issues.append("missao nao reconhecida")
    if report.get("environment") != "isolated":
        issues.append("ambiente nao isolado")
    if report.get("source") != "codex-windows":
        issues.append("origem nao identificada")
    if report.get("evidence_verified") is not True:
        issues.append("evidencias nao verificadas independentemente")
    if report.get("supervisor_approved") is not True:
        issues.append("aprovacao da supervisao independente ausente")
    tests = report.get("tests")
    if not isinstance(tests, dict) or tests.get("total") != 5 or tests.get("passed") != 5 or tests.get("failed") != 0:
        issues.append("testes insuficientes ou com falha")
    files = report.get("changed_files")
    if not isinstance(files, list) or not files or any(not isinstance(x, str) for x in files) or set(files) - ALLOWED:
        issues.append("arquivos alterados fora da allowlist")
    if report.get("original_data_preserved") is not True:
        issues.append("integridade dos dados nao comprovada")
    digest = report.get("diff_sha256")
    if not isinstance(digest, str) or len(digest) != 64 or any(c not in "0123456789abcdef" for c in digest):
        issues.append("hash de diff ausente ou invalido")
    return {"mission_id": "TOOL-CODE-02", "decision": "approved" if not issues else "pending_external_verification",
            "can_advance": not issues, "issues": issues}

def main() -> int:
    if len(sys.argv) != 2:
        print("Uso: python gate.py report.json", file=sys.stderr)
        return 2
    try:
        report = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
        if not isinstance(report, dict):
            raise ValueError("report precisa ser objeto JSON")
        result = evaluate(report)
    except (OSError, ValueError, UnicodeError) as exc:
        result = {"decision": "pending_external_verification", "can_advance": False, "issues": [str(exc)]}
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0 if result["can_advance"] else 1

if __name__ == "__main__":
    sys.exit(main())
