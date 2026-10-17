import json
import tempfile
import unittest
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from home_lab_generator import save_response
from home_lab_contract import validate_home


class HomeLabGeneratorTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.output = Path(self.temp.name) / 'output'
        self.valid = json.dumps({
            'index.html': '<!doctype html><html><head><link rel="stylesheet" href="styles.css"></head><body><script src="script.js"></script></body></html>',
            'styles.css': 'body { margin: 0; }',
            'script.js': 'document.documentElement.dataset.ready = "yes";',
        })

    def test_writes_and_validates_expected_files(self):
        self.assertEqual(len(save_response(self.valid, self.output)), 3)
        self.assertTrue(validate_home(self.output)['validated'])

    def test_rejects_extra_file(self):
        payload = json.loads(self.valid)
        payload['unexpected.txt'] = 'no'
        with self.assertRaises(ValueError):
            save_response(json.dumps(payload), self.output)

    def test_rejects_empty_file(self):
        payload = json.loads(self.valid)
        payload['script.js'] = ''
        with self.assertRaises(ValueError):
            save_response(json.dumps(payload), self.output)

    def test_never_overwrites_existing_output(self):
        save_response(self.valid, self.output)
        with self.assertRaises(FileExistsError):
            save_response(self.valid, self.output)

    def test_rejects_missing_local_assets(self):
        payload = json.loads(self.valid)
        payload['index.html'] = '<html></html>'
        save_response(json.dumps(payload), self.output)
        with self.assertRaises(ValueError):
            validate_home(self.output)


if __name__ == '__main__':
    unittest.main()
