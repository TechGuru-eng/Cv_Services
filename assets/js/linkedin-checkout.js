(() => {
 'use strict';
 const form = document.getElementById('orderForm');
 if (!form) return;
 const key = 'hireme.linkedin.lead-token';
 let token;
 try { token = sessionStorage.getItem(key); } catch (_) {}
 if (!/^[a-f0-9]{64}$/.test(token || '')) {
  token = Array.from(crypto.getRandomValues(new Uint8Array(32)), byte => byte.toString(16).padStart(2,'0')).join('');
  try { sessionStorage.setItem(key, token); } catch (_) {}
 }
 const input = document.createElement('input'); input.type='hidden'; input.name='lead_token'; input.value=token; form.append(input);
 let timer, lastPayload = '', finished = false;
 const field = name => form.elements.namedItem(name)?.value || '';
 function save(exit = false) {
  if (finished) return;
  const payload = JSON.stringify({ token, name:field('name'), first_name:field('first_name'), email:field('email'), phone:field('phone'), country:field('country'), package_id:field('package_id'), step: typeof currentStep === 'number' ? currentStep : 1 });
  if (payload === lastPayload) return;
  if (!field('email') && !field('phone')) return;
  if (exit && navigator.sendBeacon) { navigator.sendBeacon('../api/linkedin/save_checkout_lead.php', new Blob([payload],{type:'application/json'})); return; }
  fetch('../api/linkedin/save_checkout_lead.php',{method:'POST',headers:{'Content-Type':'application/json'},body:payload,keepalive:true}).then(r=>r.ok?r.json():null).then(r=>{if(r?.success)lastPayload=payload;}).catch(()=>{});
 }
 form.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(save,1200);});
 form.addEventListener('change',()=>{clearTimeout(timer);timer=setTimeout(save,300);});
 form.addEventListener('focusout',()=>save());
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='hidden')save(true);});
 addEventListener('pagehide',()=>save(true));
 document.addEventListener('linkedin-step-change',()=>save());
 document.addEventListener('linkedin-order-created',()=>{finished=true;clearTimeout(timer);try{sessionStorage.removeItem(key);}catch(_){}});
})();
