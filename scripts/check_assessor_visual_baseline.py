#!/usr/bin/env python3
"""Fail-closed guard for Assessor Online approved visual reference.

This validates the approval manifest and integrity of its screenshot.
It is not a pixel-diff or proof of visual conformity.
"""
import hashlib
import json
import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "docs/assessor-online/approved-visual-baseline.json"
VIEWS = {"desktop", "mobile-390", "mobile-320"}

def main():
    if not MANIFEST.is_file():
        raise ValueError("Visual approval manifest missing: approval gate BLOCKED")
    payload = json.loads(MANIFEST.read_text(encoding="utf-8"))
    if payload.get("status") != "approved" or not payload.get("approved_at") or not payload.get("source"):
        raise ValueError("No traceable approved visual baseline: gate BLOCKED")
    refs = payload.get("references")
    if not isinstance(refs, list) or {r.get("viewport") for r in refs if isinstance(r, dict)} != VIEWS:
        raise ValueError("Expected desktop, mobile-390 and mobile-320 references")
    for entry in refs:
        target = (ROOT / entry["path"]).resolve()
        if not target.is_relative_to(ROOT) or not target.is_file() or target.suffix.lower() != ".png":
            raise ValueError("Invalid or missing reference image")
        digest = hashlib.sha256(target.read_bytes()).hexdigest()
        if digest != entry.get("sha256"):
            raise ValueError("Reference integrity mismatch: " + entry["viewport"])
    print("Integrity gate passed; visual comparison still required.")

if __name__ == "__main__":
    try:
        main()
    except (ValueError, KeyError, OSError, json.JSONDecodeError) as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)
