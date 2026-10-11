const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/spin-wheel.js'), 'utf8');
function fixture() {
  const dom = new JSDOM('<body><input id="promoInput"><div id="order"></div></body>', {url:'https://example.test/', runScripts:'outside-only'});
  const w = dom.window;
  w.HTMLCanvasElement.prototype.getContext = () => new Proxy({}, {get: () => () => {}});
  w.eval(source.replace(/document.addEventListener\('DOMContentLoaded',[\s\S]*$/, '') + '\nwindow.Wheel = SpinWheel;');
  const wheel = new w.Wheel();
  wheel.slices = [0,10,20,30,40].map(value => ({value, text:`-${value}%`, color:'#fff'}));
  wheel.popupData = {token:'token'};
  wheel.setupDOM();
  wheel.bindEvents();
  wheel.fireConfetti = () => {};
  w.setTimeout = fn => fn();
  return {dom,w,wheel};
}
for (const value of [0,10,20,30,40]) test(`server-selected ${value}% result and single play`, async () => {
  const {dom,w,wheel} = fixture(); let requests=0;
  w.fetch = async () => {requests++; return {json:async () => ({success:true, reward:{value,text:`-${value}%`,code:value ? `CODE${value}` : null}})};};
  await wheel.spin();
  assert.equal(wheel.winner.value, value);
  assert.equal(w.localStorage.getItem('hireme_spin_played'), 'true');
  await wheel.spin(); assert.equal(requests,1);
  if (!value) assert.equal(w.document.getElementById('couponCode').style.display,'none');
  else assert.equal(w.document.getElementById('couponCode').textContent,`CODE${value}`);
  dom.window.close();
});
test('order placed or dismissal suppresses popup', () => {
  const {dom,w,wheel}=fixture();
  for (const key of ['hireme_order_placed','hireme_spin_session_dismissed']) {
    w.sessionStorage.setItem(key,'true'); wheel.show();
    assert.equal(wheel.modal.classList.contains('active'),false);
    w.sessionStorage.removeItem(key);
  }
  dom.window.close();
});
test('server rejects repeat IP and UI offers no replay', async () => {
  const {dom,w,wheel}=fixture();
  w.fetch=async () => ({json:async () => ({success:false,played:true,message:'Already played'})});
  await wheel.spin();
  assert.equal(w.localStorage.getItem('hireme_spin_played'),'true');
  assert.equal(w.document.getElementById('spinTrigger').disabled,true);
  dom.window.close();
});
test('claim validates the winning code and hands it to checkout', async () => {
  const {dom,w,wheel}=fixture(); let applied;
  wheel.winner={value:20,code:'WIN20'};
  w.fetch=async url => {
    assert.equal(url,'api/check_promo.php?code=WIN20');
    return {json:async () => ({valid:true,code:'WIN20',discount_value:20,products:['etudiant','pro','senior']})};
  };
  w.applyDiscount=(reward,manual) => {applied={reward,manual};};
  w.document.getElementById('order').scrollIntoView=()=>{};
  await wheel.resultView.querySelector('.btn-play').onclick({preventDefault(){}});
  assert.equal(w.document.getElementById('promoInput').value,'WIN20');
  assert.equal(applied.reward.discount_value,20);
  assert.equal(applied.manual,true);
  dom.window.close();
});
