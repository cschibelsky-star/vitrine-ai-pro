from pathlib import Path


def validate_home(folder):
    root = Path(folder)
    expected = ('index.html', 'styles.css', 'script.js')
    missing = [name for name in expected if not (root / name).is_file()]
    if missing:
        raise ValueError('Missing generated files: ' + ', '.join(missing))
    html = (root / 'index.html').read_text(encoding='utf-8')
    if '<html' not in html.lower():
        raise ValueError('Generated HTML is invalid')
    if 'styles.css' not in html or 'script.js' not in html:
        raise ValueError('Local assets are not referenced')
    return {'files': list(expected), 'validated': True}
