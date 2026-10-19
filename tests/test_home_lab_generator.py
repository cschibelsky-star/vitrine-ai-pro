import json
import tempfile
import unittest
from pathlib import Path
import sys
from unittest.mock import patch, MagicMock

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from home_lab_generator import save_response
from home_lab_contract import validate_home
from home_lab_gemini_client import generate


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
        with self.assertRaises(ValueError):
            save_response(json.dumps(payload), self.output)
        self.assertFalse(self.output.exists())

    def test_rejects_invalid_html_without_output(self):
        payload = json.loads(self.valid)
        payload['index.html'] = 'not html styles.css script.js'
        with self.assertRaises(ValueError):
            save_response(json.dumps(payload), self.output)
        self.assertFalse(self.output.exists())

    def test_validator_rejects_remote_assets_and_base_urls(self):
        save_response(self.valid, self.output)
        original = json.loads(self.valid)['index.html']
        variants = [
            original.replace('href="styles.css"', 'href="https://example.invalid/styles.css"'),
            original.replace('src="script.js"', 'src="//example.invalid/script.js"'),
            original.replace('</body>', '<script src="https://example.invalid/extra.js"></script></body>'),
            original.replace('<head>', '<head><base href="https://example.invalid/">'),
            '<html><!-- styles.css script.js --></html>',
        ]
        for html in variants:
            with self.subTest(html=html):
                (self.output / 'index.html').write_text(html)
                with self.assertRaises(ValueError):
                    validate_home(self.output)

    def test_accepts_dot_relative_assets_and_empty_destination(self):
        self.output.mkdir()
        payload = json.loads(self.valid)
        payload['index.html'] = payload['index.html'].replace('"styles.css"', '"./styles.css"').replace('"script.js"', '"./script.js"')
        save_response(json.dumps(payload), self.output)
        self.assertTrue(validate_home(self.output)['validated'])

    def test_invalid_generation_preserves_empty_destination_and_cleans_staging(self):
        self.output.mkdir()
        payload = json.loads(self.valid)
        payload['index.html'] = 'not html'
        with self.assertRaises(ValueError):
            save_response(json.dumps(payload), self.output)
        self.assertEqual(list(self.output.iterdir()), [])
        self.assertEqual(list(self.output.parent.glob('.home-lab-*')), [])

    def test_gemini_client_rejects_invalid_response_without_network(self):
        brief = self.output.parent / 'brief.md'
        brief.write_text('Institutional homepage')
        response = MagicMock()
        payload = json.loads(self.valid)
        payload['index.html'] = 'not html'
        response.__enter__.return_value.read.return_value = json.dumps({
            'candidates': [{'content': {'parts': [{'text': json.dumps(payload)}]}}]
        }).encode()
        with patch.dict('os.environ', {'GEMINI_API_KEY': 'test-only'}), patch('home_lab_gemini_client.urlopen', return_value=response):
            with self.assertRaises(ValueError):
                generate(brief, self.output)
        self.assertFalse(self.output.exists())


if __name__ == '__main__':
    unittest.main()
