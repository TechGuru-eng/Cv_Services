"""Collect public website strings without executing PHP or exposing customer data.

Run: python tools/extract-site-translations.py
The inventory is maintenance input, not a runtime dependency.
"""
from html.parser import HTMLParser
from pathlib import Path
import html
import json
import re

ROOT = Path(__file__).resolve().parents[1]
INVENTORY = ROOT / "tools" / "translation-inventory.json"
SKIP = {"script", "style", "noscript", "code", "pre", "svg", "textarea"}
ATTRIBUTES = {"placeholder", "title", "alt", "aria-label"}


def normalized(value):
    return re.sub(r"\s+", " ", html.unescape(value)).strip()


def meaningful(value):
    return (bool(re.search(r"[A-Za-zÀ-ÿ]", value))
            and not re.match(r"^(?:https?://|\S+@\S+\.\S+|[#*]\d|\+\s*\w+\s*,)", value)
            and not re.fullmatch(r"(?=.*\d)[A-Z0-9_-]+", value))


class Collector(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack = []
        self.strings = set()
        self.templates = set()

    def add(self, text):
        text = normalized(text)
        if not meaningful(text) or len(text) > 12000:
            return
        if "__PHP__" in text:
            # Only text around dynamic values is collected, never their actual data.
            count = 0
            def slot(_):
                nonlocal count
                result = "{" + str(count) + "}"
                count += 1
                return result
            template = re.sub(r"__PHP__", slot, text)
            if meaningful(re.sub(r"\{\d+\}", "", template)):
                self.templates.add(template)
            for fragment in text.split("__PHP__"):
                if meaningful(normalized(fragment)):
                    self.strings.add(normalized(fragment))
        else:
            self.strings.add(text)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        ignored = tag in SKIP or attrs.get("translate") == "no" or "data-no-translate" in attrs or "notranslate" in attrs.get("class", "").split()
        inherited = any(flag for _, flag in self.stack)
        if not (ignored or inherited):
            for name in ATTRIBUTES:
                if attrs.get(name): self.add(attrs[name])
            if tag == "input" and attrs.get("type") in {"submit", "button", "reset"} and attrs.get("value"):
                self.add(attrs["value"])
        if tag not in {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"}:
            self.stack.append((tag, ignored or inherited))

    def handle_endtag(self, tag):
        for index in range(len(self.stack) - 1, -1, -1):
            if self.stack[index][0] == tag:
                del self.stack[index:]
                break

    def handle_data(self, data):
        if self.stack and not any(flag for _, flag in self.stack): self.add(data)


def public_pages():
    for folder in (ROOT, ROOT / "club", ROOT / "linkedin", ROOT / "premium-ebook-page"):
        for path in sorted([*folder.glob("*.html"), *folder.glob("*.php")]):
            if "site-language.js" in path.read_text(encoding="utf-8-sig"):
                yield path


def extract():
    strings, templates, pages = set(), set(), {}
    for path in public_pages():
        source = path.read_text(encoding="utf-8-sig")
        parser = Collector()
        parser.feed(re.sub(r"<\?[\s\S]*?\?>", "__PHP__", source))
        strings.update(parser.strings)
        templates.update(parser.templates)
        pages[path.relative_to(ROOT).as_posix()] = sorted(parser.strings)
        # Inline JS UI messages and templates are part of the public interface too.
        for block in re.findall(r"<script\b[^>]*>([\s\S]*?)</script>", source, re.I):
            for quote, value in re.findall(r"(['\"`])((?:\\.|(?!\1)[^\\])*?)\1", block):
                if "<" in value or "\n" in value or "\\n" in value or len(value) > 1000:
                    continue
                value = value.replace("\\'", "'").replace('\\"', '"')
                value = normalized(value)
                # Exclude selectors, URLs, code identifiers and other program data.
                if not meaningful(value) or not re.search(r"\s|[éèàêçùîôûÉÈÀ]", value):
                    continue
                if re.search(r"[=;]|^\.|^#|https?://|document\.|function\b|(?:^| )(?:flex|items-center|rounded-|bg-|text-|font-|border-|gap-|p-\d|col-span-|transition-)", value):
                    continue
                if "${" in value:
                    count = 0
                    def replace(_):
                        nonlocal count
                        result = "{" + str(count) + "}"
                        count += 1
                        return result
                    value = re.sub(r"\$\{[^}]+\}", replace, value)
                    templates.add(value)
                else:
                    strings.add(value)
    # External public JS modules contain audit/pop-up interface text.
    for path in (ROOT / "assets" / "js").glob("*.js"):
        if path.name.startswith("site-language"): continue
        source = path.read_text(encoding="utf-8-sig")
        for fragment in re.findall(r">([^<>`\n]+)<", source):
            value = normalized(fragment)
            if meaningful(value) and "${" not in value and not re.search(r"[{};]", value): strings.add(value)
        for value in re.findall(r"(?:textContent|innerText|placeholder)\s*=\s*['\"]([^'\"]+)['\"]", source):
            if meaningful(normalized(value)): strings.add(normalized(value))
    # API validation messages are inserted into the public pages at runtime.
    for folder in (ROOT, ROOT / "api", ROOT / "api" / "linkedin"):
        for path in folder.glob("*.php"):
            source = path.read_text(encoding="utf-8-sig")
            if "$_SESSION['admin']" in source or "$_SESSION['admin_logged_in']" in source: continue
            for value in re.findall(r"['\"](?:message|error)['\"]\s*=>\s*(['\"])((?:\\.|(?!\1)[^\\])*?)\1", source):
                message = normalized(value[1].replace("\\'", "'").replace('\\"', '"'))
                if meaningful(message) and "<" not in message and "$" not in message: strings.add(message)
    templates = {value for value in templates if not re.search(r"(?:^| )(?:cv-paper|flex|items-start|transition-all)|^tel:|^translateX", value)}
    return {"strings": sorted(strings), "templates": sorted(templates), "pages": pages}


if __name__ == "__main__":
    inventory = extract()
    INVENTORY.write_text(json.dumps(inventory, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Collected {len(inventory['strings'])} strings and {len(inventory['templates'])} dynamic templates from {len(inventory['pages'])} public pages.")
