#!/usr/bin/env python3
"""Read-only source audit. Matches are not evidence of API consumption."""
import argparse
import json
import re
from pathlib import Path

AFFECTED = {
    "gemini-omni-flash-preview": "omni_interactions",
    "veo-3.1-generate-preview": "veo_cloud_or_omni_adapter",
    "veo-3.1-fast-generate-preview": "veo_cloud_or_omni_adapter",
    "veo-3.1-lite-generate-preview": "veo_cloud_or_omni_adapter",
}
PATTERN = re.compile(r"(?<![\w.-])(" + "|".join(map(re.escape, AFFECTED)) + r")(?![\w.-])")
SUFFIXES = {".php", ".json", ".yaml", ".yml", ".js", ".ts", ".tsx"}
EXCLUDED = {"vendor", "node_modules", "storage", "logs", "secrets", ".git", "tests"}
def audit(root):
    root = Path(root).resolve()
    matches = []
    for directory in ("app", "config", "routes"):
        base = root / directory
        if not base.is_dir() or base.is_symlink():
            continue
        for path in sorted(base.rglob("*")):
            relative = path.relative_to(root)
            if path.is_symlink() or not path.is_file():
                continue
            if any(part.startswith(".") or part in EXCLUDED for part in relative.parts):
                continue
            if path.suffix not in SUFFIXES:
                continue
            try:
                path.resolve().relative_to(root)
                if path.stat().st_size > 2 * 1024 * 1024:
                    continue
                source = path.read_text(encoding="utf-8")
            except (OSError, ValueError, UnicodeError):
                continue
            for line_number, line in enumerate(source.splitlines(), 1):
                for match in PATTERN.finditer(line):
                    model = match.group(0)
                    matches.append({"path": relative.as_posix(), "line": line_number,
                                    "model": model, "migration_family": AFFECTED[model],
                                    "evidence": "source_reference_not_usage_evidence"})
    return {"shutdown_date": "2026-10-22", "affected_references": matches,
            "google_project_attribution": "not_established"}
def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", type=Path, default=Path("."))
    parser.add_argument("--fail-on-affected", action="store_true")
    args = parser.parse_args()
    if not args.root.is_dir():
        parser.error("root must be an existing directory")
    result = audit(args.root)
    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 2 if args.fail_on_affected and result["affected_references"] else 0
if __name__ == "__main__":
    raise SystemExit(main())
