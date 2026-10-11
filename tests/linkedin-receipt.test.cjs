const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const base = process.env.MONCV_TEST_URL || 'http://127.0.0.1:8787';
function php(code) {
  return execFileSync('php', ['-r', code], { cwd: root, encoding: 'utf8' });
}
test('Authorised order receipt downloads as a private PDF', async () => {
  const order = JSON.parse(php('require "admin/config/db.php"; echo json_encode($pdo->query("SELECT reference, access_token FROM linkedin_orders ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC));'));
  assert.ok(order, 'An existing local test order is required');
  const query = new URLSearchParams({ ref: order.reference, token: order.access_token });
  const response = await fetch(`${base}/api/linkedin/receipt.php?${query}`);
  assert.equal(response.status, 200);
  assert.equal(response.headers.get('content-type'), 'application/pdf');
  assert.match(response.headers.get('content-disposition'), /attachment; filename="recu-[A-Z0-9-]+\.pdf"/);
  assert.match(response.headers.get('cache-control'), /no-store/);
  assert.equal(Buffer.from(await response.arrayBuffer()).subarray(0, 5).toString(), '%PDF-');
  query.set('token', '0'.repeat(32));
  const denied = await fetch(`${base}/api/linkedin/receipt.php?${query}`);
  assert.equal(denied.status, 403);
  const missing = await fetch(`${base}/api/linkedin/receipt.php?ref=${order.reference}`);
  assert.equal(missing.status, 403);
});
test('Checkout lists eight independent shop purchases without adding them to the order', async () => {
  const html = await (await fetch(`${base}/linkedin/commander?package=1`)).text();
  const { JSDOM } = require(require.resolve('jsdom', { paths: [process.env.MONCV_TEST_MODULES || __dirname] }));
  const dom = new JSDOM(html);
  const links = [...dom.window.document.querySelectorAll('.hm-resource-card a')];
  assert.equal(links.length, 8);
  assert.equal(new Set(links.map(link => link.href)).size, 8);
  for (const link of links) {
    const url = new URL(link.href);
    assert.equal(url.origin, 'https://boutique.hiremeguide.com');
    assert.notEqual(url.pathname, '/');
    assert.equal(link.rel, 'noopener noreferrer');
  }
  assert.equal(dom.window.document.querySelectorAll('[name="product_ids[]"]').length, 0);
  dom.window.close();
});
