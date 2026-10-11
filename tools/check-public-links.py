"""Check static public navigation destinations without submitting forms.

Run: python tools/check-public-links.py
Dynamic database links and external destinations need separate browser checks.
"""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin, urlsplit, unquote
import re

ROOT = Path(__file__).resolve().parents[1]
ORIGIN = 'https://example.test/MonCv/'


class Page(HTMLParser):
    def __init__(self, text):
        super().__init__()
        self.links, self.ids = [], set()
        self.feed(re.sub(r'<\?.*?\?>', '__DYNAMIC__', text, flags=re.S))

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if 'id' in values: self.ids.add(values['id'])
        if tag == 'a' and values.get('href'): self.links.append((values['href'], values.get('onclick', '')))


def destination(path):
    path = unquote(path)
    if not path.startswith('/MonCv/'): return None
    route = path[len('/MonCv/'):].rstrip('/')
    if re.fullmatch(r'blog/[\w-]+', route): return ROOT / 'article.php'
    if re.fullmatch(r'r/[A-Za-z0-9]+', route): return ROOT / 'r.php'
    if re.fullmatch(r'linkedin/commande/[\w-]+', route): return ROOT / 'linkedin/commande.php'
    if route in ['suivi-linkedin', 'linkedin/suivi-linkedin']: return ROOT / 'linkedin/suivi.php'
    candidate = ROOT / route
    choices = [candidate / 'index.html', candidate / 'index.php'] if candidate.is_dir() else [candidate, Path(str(candidate) + '.html'), Path(str(candidate) + '.php')]
    return next((item for item in choices if item.is_file()), None)


def main():
    errors, count, pages = [], 0, []
    for folder in [ROOT, ROOT / 'linkedin', ROOT / 'club']:
        for file in folder.iterdir():
            if file.suffix in ['.php', '.html'] and 'site-language.js' in file.read_text(encoding='utf-8', errors='replace'):
                pages.append(file)
    for file in pages:
        relative = file.relative_to(ROOT).as_posix()
        text = file.read_text(encoding='utf-8')
        page = Page(text)
        # Explicit bases make links stable for direct and rewritten public URLs.
        base = ORIGIN + ('linkedin/' if relative.startswith('linkedin/') else '' if relative == 'article.php' else relative)
        for href, onclick in page.links:
            if '__DYNAMIC__' in href or '$' in href: continue
            url = urlsplit(urljoin(base, href))
            if url.netloc != 'example.test' or url.scheme not in ['http', 'https']: continue
            if href == '#' and onclick: continue
            count += 1
            target = destination(url.path)
            if target is None:
                errors.append(f'{relative}: missing destination {href}')
            elif url.fragment:
                ids = Page(target.read_text(encoding='utf-8', errors='replace')).ids
                if unquote(url.fragment) not in ids: errors.append(f'{relative}: missing section {href}')
    for alias in ['linkedin', 'linkedin/', 'linkedin/commander', 'linkedin/diagnostic', 'suivi-linkedin', 'linkedin/suivi-linkedin', 'linkedin/commande/EXAMPLE', 'blog/example-article']:
        if destination('/MonCv/' + alias) is None: errors.append('Missing route: ' + alias)
    print(f'Checked {count} internal links on {len(pages)} public pages, plus route aliases.')
    if errors:
        print('\n'.join(errors))
        raise SystemExit(1)
    print('All static public link destinations and section anchors resolve.')


if __name__ == '__main__': main()
