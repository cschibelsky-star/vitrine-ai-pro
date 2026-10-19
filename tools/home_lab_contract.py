from pathlib import Path
from html.parser import HTMLParser


class HomeParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.has_html = False
        self.has_styles = False
        self.has_script = False

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'html':
            self.has_html = True
        if tag == 'base':
            raise ValueError('Base URLs are not allowed')
        if tag == 'script' and 'src' in attrs:
            if attrs['src'] not in ('script.js', './script.js'):
                raise ValueError('Only the local script.js is allowed')
            self.has_script = True
        if tag == 'link' and 'stylesheet' in (attrs.get('rel') or '').lower().split():
            if attrs.get('href') not in ('styles.css', './styles.css'):
                raise ValueError('Only the local styles.css is allowed')
            self.has_styles = True

    handle_startendtag = handle_starttag


def validate_home(folder):
    root = Path(folder)
    expected = ('index.html', 'styles.css', 'script.js')
    missing = [name for name in expected if not (root / name).is_file()]
    if missing:
        raise ValueError('Missing generated files: ' + ', '.join(missing))
    html = (root / 'index.html').read_text(encoding='utf-8')
    parser = HomeParser()
    parser.feed(html)
    parser.close()
    if not parser.has_html:
        raise ValueError('Generated HTML is invalid')
    if not parser.has_styles or not parser.has_script:
        raise ValueError('Local assets are not referenced')
    return {'files': list(expected), 'validated': True}
