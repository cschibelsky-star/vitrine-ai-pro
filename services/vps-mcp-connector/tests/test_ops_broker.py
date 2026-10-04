from __future__ import annotations

import importlib
import json
import os
import subprocess
import sys
import tarfile
import tempfile
import unittest
from pathlib import Path


class PreserveGitWorktreeTest(unittest.TestCase):
    def setUp(self) -> None:
        self.temp = tempfile.TemporaryDirectory()
        root = Path(self.temp.name)
        self.repo = root / "repo"
        self.backups = root / "backups"
        self.repo.mkdir()

        subprocess.run(["git", "init", "-b", "main"], cwd=self.repo, check=True, capture_output=True)
        subprocess.run(["git", "config", "user.email", "test@example.test"], cwd=self.repo, check=True)
        subprocess.run(["git", "config", "user.name", "Connector Test"], cwd=self.repo, check=True)
        (self.repo / "tracked.txt").write_text("original\n", encoding="utf-8")
        subprocess.run(["git", "add", "tracked.txt"], cwd=self.repo, check=True)
        subprocess.run(["git", "commit", "-m", "initial"], cwd=self.repo, check=True, capture_output=True)

        (self.repo / "tracked.txt").write_text("modified\n", encoding="utf-8")
        (self.repo / "untracked.txt").write_text("preserve me\n", encoding="utf-8")

        os.environ.setdefault("OPS_BROKER_TOKEN", "test-token")
        sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
        self.module = importlib.import_module("ops_broker")
        self.module.APP_ROOT = self.repo.resolve()
        self.module.GIT_PRESERVATION_ROOT = self.backups.resolve()
        self.module.AUDIT_LOG = root / "audit.jsonl"

    def tearDown(self) -> None:
        if sys.path:
            sys.path.pop(0)
        self.temp.cleanup()

    def git_status(self) -> bytes:
        return subprocess.run(
            ["git", "status", "--porcelain=v1", "-z"],
            cwd=self.repo,
            check=True,
            capture_output=True,
        ).stdout

    def test_preserves_dirty_tree_without_changing_it(self) -> None:
        before = self.git_status()

        result = self.module.preserve_git_worktree(
            self.module.PreserveGitRequest(confirm="EXECUTAR")
        )

        after = self.git_status()
        self.assertEqual(before, after)
        self.assertTrue(result["ok"])
        self.assertTrue(result["worktree_unchanged"])

        snapshot = Path(result["path"])
        self.assertTrue((snapshot / "head.bundle").is_file())
        self.assertTrue((snapshot / "tracked.patch").is_file())
        self.assertTrue((snapshot / "untracked.tar.gz").is_file())
        self.assertTrue((snapshot / "manifest.json").is_file())

        manifest = json.loads((snapshot / "manifest.json").read_text(encoding="utf-8"))
        self.assertEqual(
            subprocess.run(["git", "rev-parse", "HEAD"], cwd=self.repo, check=True, capture_output=True, text=True).stdout.strip(),
            manifest["head"],
        )
        self.assertEqual(1, manifest["untracked_entries"])
        self.assertEqual([], manifest["skipped_entries"])

        subprocess.run(
            ["git", "bundle", "verify", str(snapshot / "head.bundle")],
            cwd=self.repo,
            check=True,
            capture_output=True,
        )
        with tarfile.open(snapshot / "untracked.tar.gz", "r:gz") as archive:
            self.assertIn("untracked.txt", archive.getnames())

    def test_requires_explicit_confirmation(self) -> None:
        with self.assertRaises(Exception) as raised:
            self.module.preserve_git_worktree(
                self.module.PreserveGitRequest(confirm="")
            )
        self.assertEqual(409, raised.exception.status_code)


if __name__ == "__main__":
    unittest.main()
