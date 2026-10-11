const {test}=require('node:test');
const assert=require('node:assert/strict');
const {JSDOM}=require(require.resolve('jsdom',{paths:[process.env.MONCV_TEST_MODULES||__dirname]}));
async function checkout(){
 const html=await (await fetch('http://127.0.0.1:8787/linkedin/commander?package=999999')).text();
 const dom=new JSDOM(html,{url:'http://127.0.0.1:8787/linkedin/commander',runScripts:'outside-only'});
 dom.window.scrollTo=()=>{};
 const scripts=[...dom.window.document.querySelectorAll('script:not([src])')];
 dom.window.fetch=async()=>({json:async()=>({valid:true,discount_type:'percent',discount_value:20})});
 dom.window.eval(scripts.at(-1).textContent);
 return dom;
}
test('Invalid package URL defaults to an available package',async()=>{const d=await checkout();assert.ok(d.window.document.querySelector('[name=package_id]:checked'));d.window.close()});
test('Malformed email cannot advance past contact details',async()=>{const d=await checkout();const w=d.window;for(const el of w.document.querySelectorAll('#stepPanel1 input[required]'))el.value=el.type==='url'?'https://www.linkedin.com/in/example':el.type==='email'?'invalid-email':'Example';w.nextStep(2);assert.equal(w.document.querySelector('#stepPanel2').classList.contains('hidden'),true);assert.equal(w.document.querySelector('#errorAlert').classList.contains('hidden'),false);w.close()});
test('Percentage discount recalculates when selected options change',async()=>{const d=await checkout();const w=d.window;w.document.querySelector('#linkedinPromoInput').value='TEST';await w.applyLinkedInPromo();const pkg=w.document.querySelector('[name=package_id]:checked');pkg.setAttribute('data-price','20000');w.calculateTotal();assert.match(w.document.querySelector('#sumTotalPrice').innerText,/16.?000/);w.close()});
test('Submitting before payment step makes no order request',async()=>{const d=await checkout();let sent=0;d.window.fetch=async()=>{sent++;return {json:async()=>({})}};d.window.document.querySelector('#orderForm').dispatchEvent(new d.window.Event('submit',{cancelable:true}));assert.equal(sent,0);d.window.close()});

test('Package details expand independently and announce their state',async()=>{
 const d=await checkout();
 try {
  const buttons=[...d.window.document.querySelectorAll('.li-package-details')];
  assert.equal(buttons.length,3);
  const list=d.window.document.getElementById(buttons[2].getAttribute('aria-controls'));
  d.window.toggleDeliverables(buttons[2]);
  assert.equal(buttons[2].getAttribute('aria-expanded'),'true');
  assert.equal(list.classList.contains('hidden'),false);
  assert.equal(buttons[1].getAttribute('aria-expanded'),'false');
  d.window.toggleDeliverables(buttons[2]);
  assert.equal(buttons[2].getAttribute('aria-expanded'),'false');
  assert.equal(list.classList.contains('hidden'),true);
 } finally {d.window.close()}
});

