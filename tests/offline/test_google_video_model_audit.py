import importlib.util
import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[2] / "scripts/audit_google_video_models.py"
spec = importlib.util.spec_from_file_location("model_audit", SCRIPT)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

class GoogleVideoAuditTest(unittest.TestCase):
    def test_distinguishes_omni_from_all_three_veo_previews_without_exposing_source(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "config").mkdir()
            (root / "config/video.php").write_text(
                "\n".join(module.AFFECTED) + "\nsecret-value-do-not-print", encoding="utf-8")
            result = module.audit(root)
            self.assertEqual(4, len(result["affected_references"]))
            self.assertEqual("omni_interactions", result["affected_references"][0]["migration_family"])
            self.assertEqual("not_established", result["google_project_attribution"])
            self.assertNotIn("secret-value-do-not-print", json.dumps(result))

    def test_does_not_read_env_logs_tests_or_symlinked_secrets(self):
        with tempfile.TemporaryDirectory() as directory, tempfile.TemporaryDirectory() as external:
            root = Path(directory)
            (root / "config").mkdir()
            for name in (".env", ".env.php", "settings.log"):
                (root / "config" / name).write_text("veo-3.1-generate-preview", encoding="utf-8")
            target = Path(external) / "secret.php"
            target.write_text("veo-3.1-generate-preview", encoding="utf-8")
            (root / "config/link.php").symlink_to(target)
            (root / "tests").mkdir()
            (root / "tests/fixture.php").write_text("veo-3.1-generate-preview", encoding="utf-8")
            self.assertEqual([], module.audit(root)["affected_references"])

    def test_stable_and_longer_model_names_are_not_flagged(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "app").mkdir()
            (root / "app/models.php").write_text(
                "gemini-omni-1.1-flash veo-3.1-generate-001 veo-3.1-generate-preview-extra",
                encoding="utf-8")
            self.assertEqual([], module.audit(root)["affected_references"])

    def test_cli_release_gate_fails_only_when_affected_references_exist(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            command = [sys.executable, str(SCRIPT), "--root", str(root), "--fail-on-affected"]
            self.assertEqual(0, subprocess.run(command, capture_output=True).returncode)
            (root / "routes").mkdir()
            (root / "routes/api.php").write_text("veo-3.1-fast-generate-preview", encoding="utf-8")
            result = subprocess.run(command, capture_output=True, text=True)
            self.assertEqual(2, result.returncode)
            self.assertEqual(1, len(json.loads(result.stdout)["affected_references"]))

if __name__ == "__main__":
    unittest.main()
