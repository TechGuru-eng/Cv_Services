const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');

test('checkout steps return to the visible form below the progress bar', () => {
    const html = fs.readFileSync(path.join(__dirname, '../index.html'), 'utf8');
    const dom = new JSDOM(html, { runScripts: 'outside-only' });
    const w = dom.window;
    const calls = [];
    w.scrollTo = options => calls.push(options);
    w.updateStickyProgressBar = step => { w.flowStep = step; };
    w.saveProspectData = () => {};
    w.showToast = () => {};
    w.isPromoApplied = false;
    w.promoData = null;
    w.document.getElementById('stickyProgressBar').getBoundingClientRect = () => ({ height: 100 });
    for (const step of [1, 2, 3]) {
        w.document.getElementById('step' + step).getBoundingClientRect = () => ({ top: 400 });
    }
    w.eval(html.slice(html.indexOf('function goToStep(step)'), html.indexOf('// Sticky Progress Bar Logic')));
    w.goToStep(2);
    assert.equal(calls.length, 0, 'invalid details must not advance');
    for (const id of ['name', 'phone', 'email', 'city']) w.document.getElementById(id).value = 'test';
    for (const step of [1, 2, 3, 2, 1]) {
        w.goToStep(step);
        assert.equal(w.flowStep, step + 1);
        assert.equal(calls.at(-1).top, 276);
        assert.equal(calls.at(-1).behavior, 'instant');
        for (const candidate of [1, 2, 3]) {
            assert.equal(w.document.getElementById('step' + candidate).classList.contains('hidden'), candidate !== step);
        }
    }
    dom.window.close();
});
