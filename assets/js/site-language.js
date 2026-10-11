/* Shared FR/EN switch for public HTML/PHP pages. No user-entered values are translated. */
(() => {
    'use strict';
    const loader = document.currentScript;
    if (!loader || window.HireMeLanguage) return;
    // Defense in depth: never initialize on any admin route.
    if (/(?:^|\/)(?:admin(?:\.[^/]*)?|_ops)(?:\/|$)/i.test(location.pathname)) return;
    const base = new URL('../../', loader.src);
    const catalogUrl = new URL('assets/lang/en.json?v=7', base);
    const storageKey = 'hireme.public.language';
    const skipped = 'script,style,noscript,code,pre,svg,textarea,[contenteditable],[translate="no"],.notranslate,[data-no-translate]';
    const attributes = ['placeholder', 'title', 'alt', 'aria-label'];
    const records = new Map();
    let catalogPromise;
    let dictionary = Object.create(null);
    let templates = [];
    let language = 'fr';
    let revision = 0;
    let observer;
    let timer;
    let running = false;
    let dirty = false;
    let suspended = false;
    let panel;
    let status;
    const buttons = {};

    function save(value) {
        try { localStorage.setItem(storageKey, value); } catch (_) { /* Private browsing. */ }
    }
    function eligible(element) { return element && !element.closest(skipped); }
    function meaningful(text) {
        const trimmed = text ? text.trim() : '';
        return trimmed && /[A-Za-zÀ-ÿ]/.test(trimmed)
            && !/^(?:https?:\/\/|\S+@\S+\.\S+)/.test(trimmed)
            && !/^(?=.*\d)[A-Z0-9_-]+$/.test(trimmed);
    }
    function value(record) { return record.attribute ? record.node.getAttribute(record.attribute) : record.node.nodeValue; }
    function put(record, text) {
        if (record.attribute) record.node.setAttribute(record.attribute, text);
        else record.node.nodeValue = text;
        record.last = text;
    }
    function capture(node, attribute) {
        if (!node) return;
        const current = attribute ? node.getAttribute(attribute) : node.nodeValue;
        if (!meaningful(current)) return;
        let entries = records.get(node);
        if (!entries) { entries = new Map(); records.set(node, entries); }
        const key = attribute || '';
        const previous = entries.get(key);
        // App scripts can replace a price, label, or result after initial translation.
        if (!previous || (current !== previous.last && current !== previous.original)) {
            entries.set(key, { node, attribute, original: current, last: current });
        }
    }
    function collect() {
        for (const [node] of records) if (!node.isConnected) records.delete(node);
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode: node => eligible(node.parentElement) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
        });
        while (walker.nextNode()) {
            const node = walker.currentNode;
            // Translating an option without an explicit value must not change its submitted value.
            if (node.parentElement.tagName === 'OPTION' && !node.parentElement.hasAttribute('value')) {
                node.parentElement.setAttribute('value', node.parentElement.value);
            }
            capture(node);
        }
        document.querySelectorAll('[placeholder],[title],[alt],[aria-label],input[type="submit"],input[type="button"],input[type="reset"]').forEach(element => {
            if (!eligible(element)) return;
            attributes.forEach(attribute => { if (element.hasAttribute(attribute)) capture(element, attribute); });
            if (element.matches('input[type="submit"],input[type="button"],input[type="reset"]')) capture(element, 'value');
        });
        if (document.querySelector('title')) capture(document.querySelector('title').firstChild);
    }
    function allRecords() { return Array.from(records.values()).flatMap(entries => Array.from(entries.values())); }
    function apply(target) {
        observer.disconnect();
        allRecords().forEach(record => {
            if (!record.node.isConnected) return;
            const translated = translateText(record.original);
            const text = target === 'fr' ? record.original : translated === undefined ? undefined
                : record.original.match(/^\s*/)[0] + translated.trim() + record.original.match(/\s*$/)[0];
            // Only replace the version we captured; respect intervening application changes.
            if (text !== undefined && (value(record) === record.last || value(record) === record.original)) put(record, text);
        });
        document.documentElement.lang = target;
        observe();
    }
    function observe() {
        observer.observe(document.documentElement, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: attributes.concat('value') });
    }
    function updateButtons() {
        Object.entries(buttons).forEach(([code, button]) => button.setAttribute('aria-pressed', String(language === code)));
    }
    function notice(message) {
        status.textContent = message;
    }
    function normalize(text) { return text.replace(/\s+/g, ' ').trim(); }
    function escapePattern(text) { return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function compileTemplate(source, target) {
        const slots = [];
        const parts = source.split(/(\{\d+\})/);
        const pattern = parts.map(part => {
            if (/^\{\d+\}$/.test(part)) { slots.push(part); return '(.+?)'; }
            return escapePattern(part);
        }).join('');
        // Match whole interface messages; preserve captured names, references and totals.
        return { pattern: new RegExp('^' + pattern + '$'), target, slots, weight: source.replace(/\{\d+\}/g, '').length };
    }
    function translateText(text) {
        const source = normalize(text);
        if (Object.hasOwn(dictionary, source)) return dictionary[source];
        for (const template of templates) {
            const match = source.match(template.pattern);
            if (!match) continue;
            const values = Object.fromEntries(template.slots.map((slot, index) => [slot, match[index + 1]]));
            return template.target.replace(/\{\d+\}/g, slot => values[slot] ?? slot);
        }
        // Newly authored content needs an entry; never guess or corrupt personal data.
        return undefined;
    }
    function loadCatalog() {
        if (!catalogPromise) {
            catalogPromise = (async () => {
                const abort = new AbortController();
                const timeout = setTimeout(() => abort.abort(), 15000);
                try {
                    const response = await fetch(catalogUrl, { credentials: 'same-origin', signal: abort.signal });
                    if (!response.ok) throw new Error('catalog_unavailable');
                    const catalog = await response.json();
                    if (!catalog.strings || typeof catalog.strings !== 'object' || Array.isArray(catalog.strings)
                        || Object.values(catalog.strings).some(value => typeof value !== 'string')
                        || !Array.isArray(catalog.templates)) throw new Error('invalid_catalog');
                    dictionary = Object.assign(Object.create(null), catalog.strings);
                    templates = catalog.templates.filter(item => typeof item.source === 'string' && typeof item.target === 'string')
                        .map(item => compileTemplate(item.source, item.target)).sort((a, b) => b.weight - a.weight);
                } finally { clearTimeout(timeout); }
            })().catch(error => { catalogPromise = undefined; throw error; });
        }
        return catalogPromise;
    }
    async function translate() {
        if (running || language !== 'en' || suspended) { dirty = true; return; }
        running = true;
        dirty = false;
        const currentRevision = revision;
        panel.setAttribute('aria-busy', 'true');
        try {
            if (!catalogPromise) notice('Chargement de l’anglais… / Loading English…');
            await loadCatalog();
            if (currentRevision !== revision) return;
            collect();
            apply('en');
            save('en');
            notice('');
        } catch (error) {
            if (currentRevision !== revision) return;
            language = 'fr';
            suspended = true;
            apply('fr');
            save('fr');
            updateButtons();
            notice('Impossible de charger l’anglais. Réessayez avec EN. / Could not load English. Click EN to retry.');
        } finally {
            running = false;
            panel.setAttribute('aria-busy', 'false');
            if (dirty && language === 'en' && !suspended) schedule();
        }
    }
    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(translate, 250);
    }
    function choose(code) {
        revision++;
        language = code;
        suspended = false;
        clearTimeout(timer);
        updateButtons();
        if (code === 'fr') {
            collect();
            apply('fr');
            save('fr');
            notice('');
        } else if (running) dirty = true;
        else translate();
    }
    function start() {
        const stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = new URL('assets/css/site-language.css?v=4', base);
        document.head.appendChild(stylesheet);
        panel = document.createElement('div');
        panel.className = 'site-language notranslate';
        panel.setAttribute('translate', 'no');
        panel.setAttribute('role', 'group');
        panel.setAttribute('aria-label', 'Langue / Language');
        panel.setAttribute('aria-busy', 'false');
        ['fr', 'en'].forEach(code => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = code.toUpperCase();
            button.lang = code;
            button.setAttribute('aria-label', code === 'fr' ? 'Afficher en français' : 'Translate page to English');
            button.addEventListener('click', () => choose(code));
            buttons[code] = button;
            panel.appendChild(button);
        });
        status = document.createElement('div');
        status.className = 'site-language-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        panel.appendChild(status);
        const slot = document.querySelector('[data-language-slot]');
        if (slot && slot.classList.contains('home-language-slot')) {
            // A blurred header contains fixed descendants: keep the desktop
            // floating control in the body and use the header slot on mobile.
            const mobile = window.matchMedia('(max-width: 767px)');
            const placePanel = () => (mobile.matches ? slot : document.body).appendChild(panel);
            placePanel();
            mobile.addEventListener('change', placePanel);
        } else {
            (slot || document.body).appendChild(panel);
        }
        observer = new MutationObserver(changes => {
            if (language !== 'en' || suspended || !changes.some(change => eligible(change.target.nodeType === Node.TEXT_NODE ? change.target.parentElement : change.target))) return;
            dirty = true;
            schedule();
        });
        observe();
        updateButtons();
        try { if (localStorage.getItem(storageKey) === 'en') choose('en'); } catch (_) { /* French default. */ }
        window.HireMeLanguage = {
            setLanguage: code => { if (code === 'fr' || code === 'en') choose(code); },
            getLanguage: () => language,
            getUntranslated: () => [...new Set(allRecords().map(record => normalize(record.original)))].filter(text => translateText(text) === undefined),
            // Keep submitted product names independent of translated display labels.
            originalText: element => {
                if (!element) return '';
                const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
                let result = '';
                while (walker.nextNode()) {
                    const entry = records.get(walker.currentNode)?.get('');
                    result += entry && value(entry) === entry.last ? entry.original : walker.currentNode.nodeValue;
                }
                return result;
            }
        };
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
