const {chromium}=require('playwright');
const fs=require('node:fs');
const assert=require('node:assert/strict');
(async()=>{
 const browser=await chromium.launch();
 try {
  for(const width of [320,390,1440]){
   const page=await browser.newPage({viewport:{width,height:950}});
   const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   page.on('console',m=>{if(m.type()==='error')errors.push(m.text())});
   page.on('requestfailed',r=>errors.push(r.url()+': '+r.failure().errorText));
   const response=await page.goto('http://127.0.0.1:8765/demo.html');
   assert.equal(response.status(),200);
   await page.evaluate(()=>document.fonts.ready);
   async function capture(name){
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'overflow '+width+' '+name);
    const path='qa/'+name+'-'+width+'.png';
    await page.screenshot({path,fullPage:true,animations:'disabled'});
    const bytes=fs.readFileSync(path);
    assert.equal(bytes.subarray(1,4).toString(),'PNG');
    assert.equal(bytes.readUInt32BE(16),width);
    assert.ok(bytes.readUInt32BE(20)>=950);
    assert.ok(bytes.length>1000);
   }
   function navigate(options){return {click:async()=>{
    if(width<=950)await page.locator('#menu-toggle').click();
    const view=await page.locator('nav [data-view]').evaluateAll((xs,name)=>xs.find(x=>x.textContent.trim()===name)?.dataset.view,options.name);
    assert.ok(view,'Navigation target '+options.name);
    await page.locator('nav [data-view="'+view+'"]').click();
    if(width<=950)assert.equal(await page.locator('#menu-toggle').getAttribute('aria-expanded'),'false');
   }}}
   if(width<=950)await page.locator('#menu-toggle').click();
   assert.ok(await page.locator('aside>img').isVisible());
   assert.ok(await page.locator('aside>img').evaluate(img=>img.complete&&img.naturalWidth>0));
   if(width<=950)await page.locator('#sidebar-close').click();
   await capture('panel');
   const views=await page.locator('nav [data-view]').evaluateAll(xs=>xs.map(x=>({name:x.textContent,view:x.dataset.view})));
   for(const item of views){
    await navigate({name:item.name,exact:true}).click();
    assert.ok(await page.locator('#'+item.view).isVisible(),item.view);
    await capture(item.view);
   }
   await navigate({name:'Atendimentos',exact:true}).click();await page.locator('#search').fill('iluminacao');if(await page.locator('#rows tr').count()!==1)throw Error('search');await page.locator('#search').fill('zzz');if(!await page.locator('#empty').isVisible())throw Error('empty');await page.locator('#search').fill('');await page.locator('#status').selectOption('Encaminhada');if(await page.locator('#rows tr').count()!==1)throw Error('filter');
await navigate({name:'Cidadãos e contatos'}).click();
await page.locator('#contact-form input[name="name"]').fill('Teste Autorizado');
await page.locator('#contact-form input[name="district"]').fill('Centro');
await page.locator('#contact-form button[type="submit"]').click();
if(!await page.locator('#contacts-rows').getByText('Teste Autorizado').isVisible())throw Error('contacts demo');
await navigate({name:'Agenda',exact:true}).click();
await page.locator('#agenda-form input[name="title"]').fill('Agenda de teste');
await page.locator('#agenda-form input[name="day"]').fill('2026-10-24');
await page.locator('#agenda-form input[name="owner"]').fill('Equipe teste');
await page.locator('#agenda-form button[type="submit"]').click();
if(!await page.locator('#agenda-list').getByText(/Agenda de teste/).isVisible())throw Error('agenda demo');
await navigate({name:'Legislativo e emendas'}).click();
await page.locator('#legislation-form input[name="title"]').fill('Requerimento de teste');
await page.locator('#legislation-form button[type="submit"]').click();
if(!await page.locator('#legislation-rows').getByText('Requerimento de teste').isVisible())throw Error('legislation demo');
await navigate({name:'Portal do cidadão'}).click();
await page.locator('#citizen-form input[name="subject"]').fill('Assunto exemplo');
await page.locator('#citizen-form input[name="district"]').fill('Centro');
await page.locator('#citizen-form button[type="submit"]').click();
if(!await page.locator('#citizen-feedback').getByText(/DEMO-0001/).isVisible())throw Error('citizen protocol');
await page.locator('#protocol-form input[name="protocol"]').fill('DEMO-0001');
await page.locator('#protocol-form button[type="submit"]').click();
if(!await page.locator('#protocol-feedback').getByText(/Assunto exemplo/).isVisible())throw Error('protocol lookup');
await navigate({name:'Envolvimento'}).click();
if(!await page.locator('#engagement-bars .barrow').count())throw Error('engagement');
await navigate({name:'Relatórios'}).click();
if(!await page.locator('#report-total').isVisible())throw Error('reports');

   await navigate({name:'Cidadãos e contatos',exact:true}).click();
   await page.locator('#contact-search').fill('inexistente-qa');
   assert.ok(await page.locator('#contacts-empty').isVisible());
   await capture('contacts-empty');
   await navigate({name:'Atendimentos',exact:true}).click();
   await page.locator('#search').fill('inexistente-qa');
   assert.ok(await page.locator('#empty').isVisible());
   await capture('demands-empty');
   assert.deepEqual(errors,[],'JavaScript/network errors at '+width);
   await page.close();
   console.log('PASS '+width+': navigation, forms, search, filters, empty states, logo, PNG integrity, JavaScript');
  }
 } finally {await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});
