const test = require('node:test');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {JSDOM} = require('jsdom');
const path = require('node:path');
const root = path.resolve(__dirname,'..');
function render(page, query='') {
 const html=execFileSync('php',['-d','opcache.enable_cli=0','tests/page-render.php','admin/linkedin/'+page,'--html',query],{cwd:root,encoding:'utf8'});
 return new JSDOM(html,{url:'http://localhost/admin/linkedin/'+page,runScripts:'outside-only'});
}
test('all eight admin tabs render and point to real module pages',()=>{
 const pages=['index.php','commandes.php','leads.php','packages.php','upsells.php','chariow.php','parametres.php','promotions.php'];
 for(const page of pages){
  const dom=render(page), doc=dom.window.document;
  const links=[...doc.querySelectorAll('nav[aria-label="Administration LinkedIn"] a')];
  assert.equal(links.length,8);
  assert.deepEqual(links.map(a=>a.getAttribute('href')),pages.map(p=>'/admin/linkedin/'+p));
  assert.equal(doc.querySelector('nav a[aria-current="page"]').getAttribute('href'),'/admin/linkedin/'+page);
  for(const form of doc.querySelectorAll('form[method="POST"]')) assert.ok(form.querySelector('[name="csrf_token"]'),page+' protects each save action');
  dom.window.close();
 }
});
test('mobile menu opens, closes with Escape, and restores button focus',()=>{
 const dom=render('commandes.php'), doc=dom.window.document;
 for(const script of doc.querySelectorAll('script:not([src])')) dom.window.eval(script.textContent);
 const button=doc.querySelector('.li-menu-toggle'), nav=doc.querySelector('.li-admin-nav');
 assert.equal(button.getAttribute('aria-expanded'),'false');
 assert.ok(nav.classList.contains('li-menu-ready'));
 button.click(); assert.equal(button.getAttribute('aria-expanded'),'true');
 doc.querySelector('#li-admin-menu a').dispatchEvent(new dom.window.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 assert.equal(button.getAttribute('aria-expanded'),'false');assert.equal(doc.activeElement,button);
 dom.window.close();
});
test('order search, payment, status, reset and safe detail links work with live data',()=>{
 const dom=render('commandes.php'),doc=dom.window.document;
 const ref=doc.querySelector('.li-reference');assert.ok(ref,'at least one existing order for regression');
 const reference=ref.textContent;
 const exact=render('commandes.php','q='+encodeURIComponent(reference));
 assert.equal(exact.window.document.querySelectorAll('tbody tr').length,1);
 assert.equal(exact.window.document.querySelector('.li-reference').textContent,reference);
 for(const payment of ['PAYMENT_SUBMITTED','CONFIRMED','REJECTED','PENDING']){
  const filtered=render('commandes.php','payment='+payment);
  assert.equal(filtered.window.document.querySelector('[name="payment"]').value,payment);
  const labels={PAYMENT_SUBMITTED:'À vérifier',CONFIRMED:'Confirmé',REJECTED:'Rejeté',PENDING:'En attente'};
  for(const badge of filtered.window.document.querySelectorAll('td[data-label="Paiement"]')) assert.equal(badge.textContent.trim(),labels[payment]);
  filtered.window.close();
 }
 const empty=render('commandes.php','q=fixture-no-matching-reference&payment=CONFIRMED&status=TERMINE&page=999');
 assert.ok(empty.window.document.querySelector('.li-empty'));assert.ok(empty.window.document.querySelector('a[href="commandes.php"]'));
 const malformed=render('commandes.php','q[]=x&payment[]=x&status[]=x&page[]=1');
 assert.ok(malformed.window.document.querySelector('.li-orders'));
 const detail=render('order_details.php');
 for(const form of detail.window.document.querySelectorAll('form[method="POST"]')) assert.equal(form.querySelectorAll('[name="csrf_token"]').length,1);
 assert.equal(detail.window.document.querySelector('nav a[aria-current="page"]').getAttribute('href'),'/admin/linkedin/commandes.php');
 for(const link of doc.querySelectorAll('a[target="_blank"]')) assert.ok(link.rel.includes('noopener'));
 for(const d of [dom,exact,empty,malformed,detail]) d.window.close();
});

test('package and settings saves accept protected forms and roll back test changes',()=>{
 for(const page of ['packages.php','parametres.php']){
  const dom=render(page),doc=dom.window.document;
  const form=doc.querySelector('form[method="POST"]'),values=new URLSearchParams();
  for(const input of form.querySelectorAll('input[name],textarea[name],select[name]')){
   if(['checkbox','radio'].includes(input.type) && !input.checked) continue;
   values.append(input.name,input.value);
  }
  const field=page==='packages.php'?'name':'mtn_name',original=values.get(field);
  values.set(field,'Regression fixture');
  const output=execFileSync('php',['-d','opcache.enable_cli=0','tests/page-render.php','admin/linkedin/'+page,'--html','',values.toString()],{cwd:root,encoding:'utf8'});
  const saved=new JSDOM(output);
  assert.ok(saved.window.document.body.textContent.includes('mis à jour'),'save succeeds');
  assert.equal(saved.window.document.querySelector('[name="'+field+'"]').value,'Regression fixture');
  const after=render(page);assert.equal(after.window.document.querySelector('[name="'+field+'"]').value,original,'transaction restores original data');
  values.set('csrf_token','invalid');
  const rejected=execFileSync('php',['-d','opcache.enable_cli=0','tests/page-render.php','admin/linkedin/'+page,'--html','',values.toString()],{cwd:root,encoding:'utf8'});
  assert.ok(rejected.includes('CSRF mismatch'),'rejects an invalid token');
  for(const d of [dom,saved,after])d.window.close();
 }
});
