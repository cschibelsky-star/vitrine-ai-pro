"""Validate a structured model response and write Home artifacts to an empty lab folder."""
import json
from pathlib import Path
from tempfile import TemporaryDirectory

from home_lab_contract import validate_home

EXPECTED = {'index.html', 'styles.css', 'script.js'}


def save_response(response_text, destination):
    data = json.loads(response_text)
    if not isinstance(data, dict) or set(data) != EXPECTED:
        raise ValueError('Expected exactly three Home files')
    if any(not isinstance(data[name], str) or not data[name].strip() for name in EXPECTED):
        raise ValueError('Empty or invalid file')
    output = Path(destination)
    if output.exists() and any(output.iterdir()):
        raise FileExistsError('Refusing to overwrite lab output')
    output.parent.mkdir(parents=True, exist_ok=True)
    with TemporaryDirectory(prefix='.home-lab-', dir=output.parent) as temporary:
        staged = Path(temporary) / 'validated'
        staged.mkdir()
        for name in sorted(EXPECTED):
            (staged / name).write_text(data[name], encoding='utf-8')
        validate_home(staged)
        # Directory replacement is atomic and refuses a nonempty destination.
        staged.replace(output)
    return sorted(EXPECTED)
