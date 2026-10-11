const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/cv-audit.js'), 'utf8');
function audit() {
  const dom = new JSDOM('<body></body>', {runScripts:'outside-only', url:'https://example.test/'});
  dom.window.eval(source + '\nwindow.testAudit = cvAudit;');
  dom.window.testAudit.injectModal();
  dom.window.alert = () => {};
  dom.window.testAudit.jobInput.value = 'Comptable';
  Object.defineProperty(dom.window.testAudit.fileInput, 'files', {value:[new dom.window.File(['%PDF-test'], 'CV.pdf', {type:'application/pdf'})]});
  return dom;
}
test('CV analysis requires consent before making a provider request', async () => {
  const d = audit(); let requests = 0;
  d.window.fetch = async () => {requests++; throw new Error('should not run');};
  await d.window.testAudit.startAnalysis();
  assert.equal(requests, 0); d.window.close();
});
test('Unavailable CV analysis returns to the input step with a visible message', async () => {
  const d = audit(); let message = '';
  d.window.document.getElementById('auditAnalysisConsent').checked = true;
  d.window.alert = value => {message = value;};
  d.window.fetch = async () => ({json:async () => ({success:false, message:'Analyse indisponible'})});
  await d.window.testAudit.startAnalysis();
  assert.equal(message, 'Analyse indisponible');
  assert.equal(d.window.document.getElementById('auditStep1').classList.contains('active'), true);
  d.window.close();
});
test('CV findings remain text even when an AI response includes HTML', () => {
  const d = audit();
  const attack = '<img src=x onerror=alert(1)><script>alert(1)</script>';
  d.window.testAudit.showResults({score:80, sector:'Finance', breakdown:{job_match:80}, findings:[{type:'warning',text:attack}]});
  const results = d.window.document.getElementById('resultFindings');
  assert.equal(results.querySelector('img,script'), null);
  assert.ok(results.textContent.includes(attack)); d.window.close();
});
