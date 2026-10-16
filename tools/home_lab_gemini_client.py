"""Generate Home files with Gemini inside the isolated authorized lab."""
import json
import os
from pathlib import Path
from urllib.request import Request, urlopen

from home_lab_generator import save_response


def generate(brief_file, destination):
    token = os.environ.get('GEMINI_API_KEY')
    if not token:
        raise RuntimeError('Gemini credential is unavailable in the lab')
    brief = Path(brief_file).read_text(encoding='utf-8')[:20000]
    prompt = ('Create an accessible premium institutional Vitrine IA Pro homepage. '
              'Use lightweight, reproducible HTML, CSS and JavaScript. '
              'Respond as JSON with keys index.html, styles.css and script.js. '
              'No remote imagery or invented contact details. Brief:\n' + brief)
    body = json.dumps({'contents': [{'parts': [{'text': prompt}]}],
                       'generationConfig': {'responseMimeType': 'application/json'}}).encode()
    req = Request('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent',
                  data=body,
                  headers={'Content-Type': 'application/json', 'x-goog-api-key': token},
                  method='POST')
    with urlopen(req, timeout=120) as response:
        result = json.load(response)
    content = result['candidates'][0]['content']['parts'][0]['text']
    return save_response(content, destination)
