const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require(require.resolve('jsdom', { paths: [process.env.MONCV_TEST_MODULES || __dirname] }));
const source = fs.readFileSync(path.join(__dirname, '../assets/js/site-language.js'), 'utf8');

const html = `<!doctype html><html lang="fr"><head><title>Bonjour</title></head><body>
<h1> Bonjour <span>le monde</span> !</h1>
<input id="email" value="client@example.com" placeholder="Votre email">
<textarea>Mon expérience privée</textarea>
<select><option>Étudiant</option><option value="pro">Professionnel</option></select>
<button id="buy" title="Commander">Acheter</button>
<p translate="no">HireMe</p><p id="dynamic">Chargement</p>
<script>const privateText = "secret";</script></body></html>`;

function page({ url = 'https://hireme.test/MonCv/index.html', saved, fetch, setup } = {}) {
    const dom = new JSDOM(html, { url, runScripts: 'outside-only' });
    const window = dom.window;
    window.TextEncoder = TextEncoder;
    if (saved) window.localStorage.setItem('hireme.public.language', saved);
    const requests = [];
    window.fetch = fetch || (async (url, options) => {
        const payload = {};
        requests.push({ url: String(url), options });
        const dictionary = { Bonjour: 'Hello', 'le monde': 'world', 'Votre email': 'Your email', Étudiant: 'Student', Professionnel: 'Professional', Commander: 'Order', Acheter: 'Buy', Chargement: 'Loading', Disponible: 'Available' };
        return { ok: true, json: async () => ({ strings: dictionary, templates: [] }) };
    });
    const script = window.document.createElement('script');
    script.src = new URL('assets/js/site-language.js', 'https://hireme.test/MonCv/').href;
    window.document.head.appendChild(script);
    Object.defineProperty(window.document, 'currentScript', { get: () => script });
    if (setup) setup(window);
    window.eval(source);
    return { dom, window, document: window.document, requests };
}
async function until(predicate) {
    const end = Date.now() + 4000;
    while (!predicate()) {
        if (Date.now() > end) throw new Error('Timed out waiting for language state');
        await new Promise(resolve => setTimeout(resolve, 20));
    }
}
async function start(options) {
    const context = page(options);
    await until(() => context.window.HireMeLanguage || options?.url?.includes('/admin'));
    return context;
}

test('Homepage switcher stays outside the blurred desktop header and moves into the mobile header', async () => {
    let resize;
    const media = { matches: false, addEventListener: (event, handler) => { resize = handler; } };
    const { window, document } = await start({ setup(window) {
        const nav = window.document.createElement('nav');
        nav.style.backdropFilter = 'blur(10px)';
        nav.innerHTML = '<div class="home-language-slot" data-language-slot></div>';
        window.document.body.prepend(nav);
        window.matchMedia = query => {
            assert.equal(query, '(max-width: 767px)');
            return media;
        };
    } });
    try {
        const panel = document.querySelector('.site-language');
        assert.equal(panel.parentElement, document.body);
        media.matches = true;
        resize();
        assert.equal(panel.parentElement, document.querySelector('[data-language-slot]'));
        media.matches = false;
        resize();
        assert.equal(panel.parentElement, document.body);
        assert.equal(document.querySelectorAll('.site-language').length, 1);
        panel.querySelector('button').click();
        assert.equal(window.HireMeLanguage.getLanguage(), 'fr');
    } finally { window.close(); }
});

test('English preserves text spacing, field/option values, markup and restores French', async () => {
    const { window, document, requests } = await start();
    try {
        document.querySelector('#email').value = 'new@example.com';
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.documentElement.lang === 'en');
        assert.equal(document.querySelector('h1').textContent, ' Hello world !');
        assert.equal(document.querySelector('h1 span').textContent, 'world');
        assert.equal(document.title, 'Hello');
        assert.equal(document.querySelector('#email').placeholder, 'Your email');
        assert.equal(document.querySelector('#email').value, 'new@example.com');
        assert.equal(document.querySelector('textarea').value, 'Mon expérience privée');
        assert.equal(document.querySelector('select').value, 'Étudiant');
        assert.equal(document.querySelector('select option').textContent, 'Student');
        assert.equal(window.HireMeLanguage.originalText(document.querySelector('select option')), 'Étudiant');
        assert.equal(document.querySelector('[translate="no"]').textContent, 'HireMe');
        assert.equal(requests[0].url, 'https://hireme.test/MonCv/assets/lang/en.json?v=6');
        assert.equal(requests[0].options.body, undefined);
        window.HireMeLanguage.setLanguage('fr');
        assert.equal(document.querySelector('h1').textContent, ' Bonjour le monde !');
        assert.equal(document.querySelector('#email').placeholder, 'Votre email');
        assert.equal(document.querySelector('#email').value, 'new@example.com');
        assert.equal(document.title, 'Bonjour');
        assert.equal(window.localStorage.getItem('hireme.public.language'), 'fr');
    } finally { window.close(); }
});

test('New dynamic content translates, then restores its latest French source', async () => {
    const { window, document } = await start({ saved: 'en' });
    try {
        await until(() => document.documentElement.lang === 'en');
        document.querySelector('#dynamic').textContent = 'Disponible';
        await until(() => document.querySelector('#dynamic').textContent === 'Available');
        window.HireMeLanguage.setLanguage('fr');
        assert.equal(document.querySelector('#dynamic').textContent, 'Disponible');
    } finally { window.close(); }
});

test('Local catalog failure leaves French and shows a recoverable error', async () => {
    const { window, document } = await start({ fetch: async () => ({ ok: false, json: async () => ({ error: 'translation_not_configured' }) }) });
    try {
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.querySelector('.site-language-status').textContent.includes('Could not load English'));
        assert.equal(document.documentElement.lang, 'fr');
        assert.equal(document.querySelector('h1').textContent, ' Bonjour le monde !');
        assert.equal(window.HireMeLanguage.getLanguage(), 'fr');
    } finally { window.close(); }
});

test('Rapid EN/FR switching does not apply a stale translation response', async () => {
    let release;
    const { window, document } = await start({ fetch: (url, options) => new Promise(resolve => {
        release = () => resolve({ ok: true, json: async () => ({ strings: { Bonjour: 'Hello' }, templates: [] }) });
    }) });
    try {
        window.HireMeLanguage.setLanguage('en');
        await until(() => release);
        window.HireMeLanguage.setLanguage('fr');
        release();
        await new Promise(resolve => setTimeout(resolve, 50));
        assert.equal(document.documentElement.lang, 'fr');
        assert.equal(document.querySelector('h1').textContent, ' Bonjour le monde !');
    } finally { window.close(); }
});

test('Translation output stays text, not executable HTML', async () => {
    const { window, document } = await start({ fetch: async (url, options) => ({ ok: true, json: async () => ({ strings: { Bonjour: '<img src=x onerror=alert(1)>' }, templates: [] }) }) });
    try {
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.documentElement.lang === 'en');
        assert.equal(document.querySelector('h1 img'), null);
        assert.ok(document.querySelector('h1').textContent.includes('<img'));
    } finally { window.close(); }
});

test('Local templates preserve changing names and totals; unknown content is untouched', async () => {
    const { window, document } = await start({ fetch: async () => ({ ok: true, json: async () => ({ strings: {}, templates: [{ source: 'Bonjour {0}, montant : {1} FCFA', target: 'Hello {0}, amount: {1} FCFA' }] }) }) });
    try {
        document.querySelector('#dynamic').textContent = 'Bonjour Alice, montant : 5000 FCFA';
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.documentElement.lang === 'en');
        assert.equal(document.querySelector('#dynamic').textContent, 'Hello Alice, amount: 5000 FCFA');
        assert.equal(document.querySelector('h1').textContent, ' Bonjour le monde !');
        window.HireMeLanguage.setLanguage('fr');
        assert.equal(document.querySelector('#dynamic').textContent, 'Bonjour Alice, montant : 5000 FCFA');
    } finally { window.close(); }
});

test('Admin routes never initialize translation, even if the loader is accidentally present', async () => {
    for (const route of ['admin/login.php', 'admin/linkedin/commandes', 'premium-ebook-page/admin.6251/']) {
        const { window, document, requests } = page({ url: `https://hireme.test/MonCv/${route}`, saved: 'en' });
        await new Promise(resolve => setTimeout(resolve, 20));
        assert.equal(window.HireMeLanguage, undefined);
        assert.equal(document.querySelector('.site-language'), null);
        assert.equal(requests.length, 0);
        window.close();
    }
});
const catalog = JSON.parse(fs.readFileSync(path.join(__dirname, '../assets/lang/en.json'), 'utf8'));

test('Checkout promises and package labels translate to English and restore French', async () => {
    const { window, document } = await start({ fetch: async () => ({ ok: true, json: async () => catalog }) });
    try {
        const response = await fetch('http://127.0.0.1:8787/linkedin/commander');
        assert.equal(response.ok, true);
        const checkout = new JSDOM(await response.text());
        const selector = checkout.window.document.querySelector('#packageSelectorBox');
        assert.ok(selector, 'Checkout must render its package selector');
        document.body.appendChild(document.importNode(selector, true));
        checkout.window.close();
        const promises = [...document.querySelectorAll('.li-package-promise p')];
        assert.equal(promises.length, 3);
        const originals = promises.map(node => node.textContent);
        originals.forEach(text => assert.ok(catalog.strings[text], `Missing promise translation: ${text}`));
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.documentElement.lang === 'en');
        promises.forEach((node, index) => assert.equal(node.textContent, catalog.strings[originals[index]]));
        assert.match(document.querySelector('.li-package-badge').textContent, /MOST POPULAR/);
        assert.equal(document.querySelector('.li-package-details span').textContent, 'See what’s included');
        window.HireMeLanguage.setLanguage('fr');
        promises.forEach((node, index) => assert.equal(node.textContent, originals[index]));
    } finally { window.close(); }
});
test('Database CV claims are replaced by qualitative captions that translate and restore', async () => {
    const { window, document } = await start({ fetch: async () => ({ ok: true, json: async () => catalog }) });
    try {
        const section = document.createElement('section');
        section.innerHTML = '<div id="baSlideContent">' + ['baBeforeTag','baBeforeScore','baBeforeQuote','baAfterTag','baAfterScore','baAfterBadge','baAfterQuote'].map(id => `<p id="${id}"></p>`).join('') + '<img id="baBeforeImg"><img id="baAfterImg"></div>';
        document.body.appendChild(section);
        const homepage = fs.readFileSync(path.join(__dirname, '../index.html'), 'utf8');
        const render = homepage.slice(homepage.indexOf('function renderBeforeAfterSlide(index)'), homepage.indexOf('function selectBeforeAfterSlide(index)'));
        window.eval(`const beforeAfterSlides = [{before:{score:'28%',quote:'No sales metrics',img:'before.png'},after:{score:'99%',quote:'3x more interviews',img:'after.png'}}]; ${render}; renderBeforeAfterSlide(0);`);
        await until(() => document.querySelector('#baAfterQuote').textContent.length > 0);
        assert.equal(document.querySelector('#baBeforeScore').innerText, 'Présentation initiale');
        assert.equal(document.querySelector('#baAfterScore').innerText, 'Lecture facilitée');
        assert.doesNotMatch(section.textContent, /\d|quantifi|interviews|sales metrics/i);
        window.HireMeLanguage.setLanguage('en');
        await until(() => document.querySelector('#baAfterQuote').textContent === 'A clear layout that highlights experience and skills.');
        window.HireMeLanguage.setLanguage('fr');
        assert.equal(document.querySelector('#baAfterQuote').textContent, 'Une mise en page claire qui met en valeur le parcours et les compétences.');
    } finally { window.close(); }
});

