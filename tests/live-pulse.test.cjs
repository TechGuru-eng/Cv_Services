const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const html = fs.readFileSync(__dirname + '/../admin/live.php', 'utf8');
const script = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].at(-1)[1];
function element() {
  return {textContent:'',innerHTML:'',style:{},classList:{add(){},remove(){}},addEventListener(){},appendChild(){}};
}
async function run(data, failure = false) {
  const elements = new Map();
  const document = {
    getElementById(id){if (!elements.has(id)) elements.set(id,element());return elements.get(id);},
    // Lucide has replaced every <i> with <svg>; querying the old icon returns null.
    querySelector(){return null;}, createElement(){return element();}
  };
  vm.runInNewContext(script, {window:{},document,localStorage:{getItem(){return null;}},
    fetch:async()=>{if(failure)throw new Error('Offline');return {ok:true,json:async()=>data};},
    console:{error(){}},setTimeout(){},setInterval(){},Intl,Date});
  await new Promise(resolve=>setImmediate(resolve));
  return elements;
}
test('Polling works with replaced icons, no Lucide CDN and no recorded visit',async()=>{
  const elements=await run({active_now:0,timestamp:'2026-10-10 16:00:00',last_db_visit:null,today_revenue:0,visitors:[],high_intent:[],server_now_ms:Date.now()});
  assert.equal(elements.get('active-now').textContent,0);
  assert.equal(elements.get('last-update').textContent,'16:00');
  assert.equal(elements.get('system-status-text').textContent,'Optimal');
  assert.match(elements.get('last-db-sync').textContent,/Aucune visite/);
});
test('Connection failures are visible instead of reporting Optimal',async()=>{
  const elements=await run(null,true);
  assert.equal(elements.get('system-status-text').textContent,'Hors ligne');
  assert.equal(elements.get('active-now').textContent,'ERR');
});
