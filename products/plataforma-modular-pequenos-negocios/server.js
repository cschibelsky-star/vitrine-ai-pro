import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { URL } from 'node:url';

const PORT = 8788;
const dataDir = '/app/data';
const dataFile = path.join(dataDir, 'store.json');
fs.mkdirSync(dataDir, { recursive: true });

const defaultStore = {
  companies: [{ id: 1, name: 'Empresa Demonstração', modules: ['base', 'clientes', 'profissionais', 'servicos', 'agenda'] }],
  clients: [],
  professionals: [],
  services: [],
  appointments: []
};

function load() {
  try {
    const data = JSON.parse(fs.readFileSync(dataFile, 'utf8'));
    return { ...structuredClone(defaultStore), ...data };
  } catch {
    return structuredClone(defaultStore);
  }
}

function save(store) {
  const tmp = dataFile + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(store, null, 2));
  fs.renameSync(tmp, dataFile);
}

if (!fs.existsSync(dataFile)) save(defaultStore);

const json = (res, status, body) => {
  res.writeHead(status, { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' });
  res.end(JSON.stringify(body));
};

const readBody = req => new Promise((resolve, reject) => {
  let raw = '';
  req.on('data', chunk => {
    raw += chunk;
    if (raw.length > 1_000_000) req.destroy();
  });
  req.on('end', () => {
    if (!raw) return resolve({});
    try { resolve(JSON.parse(raw)); } catch (error) { reject(error); }
  });
  req.on('error', reject);
});

const clean = value => String(value ?? '').trim();
const nextId = rows => rows.reduce((max, row) => Math.max(max, Number(row.id) || 0), 0) + 1;
const companyIdFrom = (req, url) => Number(req.headers['x-company-id'] || url.searchParams.get('company_id') || 1);

function companyContext(store, companyId) {
  return store.companies.find(c => Number(c.id) === Number(companyId));
}

function requireCompany(store, companyId, res) {
  const company = companyContext(store, companyId);
  if (!company) {
    json(res, 404, { error: 'empresa_nao_encontrada' });
    return null;
  }
  return company;
}

function requireModule(company, moduleName, res) {
  if (!company.modules.includes(moduleName)) {
    json(res, 403, { error: 'modulo_nao_licenciado', module: moduleName });
    return false;
  }
  return true;
}

const labels = {
  clientes: 'Clientes',
  profissionais: 'Profissionais',
  servicos: 'Serviços',
  agenda: 'Agenda'
};

function appHtml() {
  return `<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Plataforma Modular</title>
<style>
:root{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f5f7fb}
*{box-sizing:border-box}body{margin:0}.shell{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
aside{background:#101827;color:#fff;padding:28px 20px}.brand{font-size:22px;font-weight:800}.brand small{display:block;font-size:12px;font-weight:500;color:#9fb0c8;margin-top:5px}
nav{margin-top:30px}nav button{display:block;width:100%;border:0;text-align:left;padding:12px 14px;margin:5px 0;border-radius:10px;color:#d9e2ef;background:transparent;cursor:pointer;font:inherit}
nav button:hover,nav button.active{background:#1d2a3d;color:#fff}main{padding:34px}.eyebrow{font-size:12px;font-weight:800;text-transform:uppercase;color:#667085}
h1{margin:8px 0 6px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}.card,.panel{background:#fff;border:1px solid #e5e9f0;border-radius:18px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.04)}
.card b{font-size:28px;display:block;margin-top:8px}.modules{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:20px}.module{background:#fff;padding:22px;border-radius:18px;border:1px solid #e5e9f0}
.pill{display:inline-block;font-size:12px;padding:5px 9px;background:#e9f9ef;color:#16794a;border-radius:999px}.future{opacity:.58}.toolbar{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-bottom:16px}
form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px}label{display:grid;gap:6px;font-size:12px;font-weight:700;color:#46556d}.wide{grid-column:1/-1}
input,select,textarea,button.action{font:inherit;border:1px solid #cfd7e5;border-radius:10px;padding:11px;background:#fff}button.action{cursor:pointer;background:#172033;color:#fff;border-color:#172033;font-weight:700}.danger{background:#fff!important;color:#b42318!important;border-color:#f2b8b5!important}
table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #edf0f5;padding:11px 8px;font-size:14px}th{font-size:12px;text-transform:uppercase;color:#667085}
.empty{padding:30px;text-align:center;color:#667085}.msg{min-height:20px;color:#b42318;font-size:13px}.topline{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
@media(max-width:900px){.shell{grid-template-columns:1fr}aside{display:none}.grid{grid-template-columns:1fr 1fr}.modules{grid-template-columns:1fr}form{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>
</head>
<body>
<div class="shell">
<aside>
  <div class="brand">Plataforma Modular<small>Pequenos Negócios</small></div>
  <nav>
    <button data-view="dashboard" class="active">Visão geral</button>
    <button data-view="clientes">Clientes</button>
    <button data-view="profissionais">Profissionais</button>
    <button data-view="servicos">Serviços</button>
    <button data-view="agenda">Agenda</button>
  </nav>
</aside>
<main>
  <div class="topline">
    <div><span class="eyebrow">MVP • Ambiente de homologação</span><h1 id="title">Visão geral</h1></div>
    <span class="pill" id="companyName">Empresa</span>
  </div>
  <div id="content"></div>
</main>
</div>
<script>
const content=document.querySelector('#content'),title=document.querySelector('#title');
const esc=v=>String(v??'').replace(/[&<>"]/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
const api=async(path,opts={})=>{const r=await fetch(path,{headers:{'content-type':'application/json','x-company-id':'1'},...opts});const j=await r.json().catch(()=>({}));if(!r.ok)throw new Error(j.error||'erro');return j};
const formData=f=>Object.fromEntries(new FormData(f));
async function dashboard(){
  const d=await api('/api/dashboard'); document.querySelector('#companyName').textContent=d.company.name;
  content.innerHTML=\`<div class="grid"><div class="card">Clientes<b>\${d.counts.clients}</b></div><div class="card">Profissionais<b>\${d.counts.professionals}</b></div><div class="card">Serviços<b>\${d.counts.services}</b></div><div class="card">Agendamentos<b>\${d.counts.appointments}</b></div></div>
  <h2>Módulos</h2><div class="modules">\${d.company.modules.filter(x=>x!=='base').map(k=>\`<div class="module"><span class="pill">ATIVO</span><h3>\${esc(({clientes:'Clientes',profissionais:'Profissionais',servicos:'Serviços',agenda:'Agenda'})[k]||k)}</h3><p>Módulo habilitado para esta empresa.</p></div>\`).join('')}
  <div class="module future"><span class="pill">ROADMAP</span><h3>Financeiro · Caixa · Estoque · Comissões · PDV · Marketing</h3><p>Expansão modular sem alterar o núcleo do cliente.</p></div></div>\`;
}
async function clientes(){
  const rows=await api('/api/clients'); content.innerHTML=\`<div class="panel"><div class="toolbar"><div><h2>Clientes</h2><small>Cadastro e relacionamento básico.</small></div></div>
  <form id="f"><label>Nome<input name="name" required></label><label>Telefone<input name="phone"></label><label>E-mail<input name="email" type="email"></label><label>Observações<input name="notes"></label><div class="wide"><button class="action">Adicionar cliente</button></div></form><div id="msg" class="msg"></div>
  <table><tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th></th></tr>\${rows.map(r=>\`<tr><td>\${esc(r.name)}</td><td>\${esc(r.phone)}</td><td>\${esc(r.email)}</td><td><button class="action danger" data-del-client="\${r.id}">Excluir</button></td></tr>\`).join('')||'<tr><td colspan="4" class="empty">Nenhum cliente cadastrado.</td></tr>'}</table></div>\`;
  f.onsubmit=async e=>{e.preventDefault();try{await api('/api/clients',{method:'POST',body:JSON.stringify(formData(f))});clientes()}catch(err){msg.textContent=err.message}};
  document.querySelectorAll('[data-del-client]').forEach(b=>b.onclick=async()=>{await api('/api/clients/'+b.dataset.delClient,{method:'DELETE'});clientes()});
}
async function profissionais(){
  const rows=await api('/api/professionals'); content.innerHTML=\`<div class="panel"><h2>Profissionais</h2><form id="f"><label>Nome<input name="name" required></label><label>Telefone<input name="phone"></label><label>Especialidade<input name="specialty"></label><label>Ativo<select name="active"><option value="true">Sim</option><option value="false">Não</option></select></label><div class="wide"><button class="action">Adicionar profissional</button></div></form><div id="msg" class="msg"></div>
  <table><tr><th>Nome</th><th>Especialidade</th><th>Telefone</th><th>Status</th><th></th></tr>\${rows.map(r=>\`<tr><td>\${esc(r.name)}</td><td>\${esc(r.specialty)}</td><td>\${esc(r.phone)}</td><td>\${r.active?'Ativo':'Inativo'}</td><td><button class="action danger" data-del-prof="\${r.id}">Excluir</button></td></tr>\`).join('')||'<tr><td colspan="5" class="empty">Nenhum profissional cadastrado.</td></tr>'}</table></div>\`;
  f.onsubmit=async e=>{e.preventDefault();try{await api('/api/professionals',{method:'POST',body:JSON.stringify(formData(f))});profissionais()}catch(err){msg.textContent=err.message}};
  document.querySelectorAll('[data-del-prof]').forEach(b=>b.onclick=async()=>{await api('/api/professionals/'+b.dataset.delProf,{method:'DELETE'});profissionais()});
}
async function servicos(){
  const rows=await api('/api/services'); content.innerHTML=\`<div class="panel"><h2>Serviços</h2><form id="f"><label>Serviço<input name="name" required></label><label>Duração (min)<input name="duration_minutes" type="number" min="5" value="30" required></label><label>Preço (R$)<input name="price" type="number" step="0.01" min="0" value="0"></label><label>Ativo<select name="active"><option value="true">Sim</option><option value="false">Não</option></select></label><div class="wide"><button class="action">Adicionar serviço</button></div></form><div id="msg" class="msg"></div>
  <table><tr><th>Serviço</th><th>Duração</th><th>Preço</th><th>Status</th><th></th></tr>\${rows.map(r=>\`<tr><td>\${esc(r.name)}</td><td>\${r.duration_minutes} min</td><td>R$ \${Number(r.price||0).toFixed(2)}</td><td>\${r.active?'Ativo':'Inativo'}</td><td><button class="action danger" data-del-service="\${r.id}">Excluir</button></td></tr>\`).join('')||'<tr><td colspan="5" class="empty">Nenhum serviço cadastrado.</td></tr>'}</table></div>\`;
  f.onsubmit=async e=>{e.preventDefault();try{await api('/api/services',{method:'POST',body:JSON.stringify(formData(f))});servicos()}catch(err){msg.textContent=err.message}};
  document.querySelectorAll('[data-del-service]').forEach(b=>b.onclick=async()=>{await api('/api/services/'+b.dataset.delService,{method:'DELETE'});servicos()});
}
async function agenda(){
  const [rows,clients,professionals,services]=await Promise.all([api('/api/appointments'),api('/api/clients'),api('/api/professionals'),api('/api/services')]);
  content.innerHTML=\`<div class="panel"><h2>Agenda</h2><form id="f"><label>Cliente<select name="client_id" required><option value="">Selecione</option>\${clients.map(x=>\`<option value="\${x.id}">\${esc(x.name)}</option>\`).join('')}</select></label><label>Profissional<select name="professional_id" required><option value="">Selecione</option>\${professionals.filter(x=>x.active).map(x=>\`<option value="\${x.id}">\${esc(x.name)}</option>\`).join('')}</select></label><label>Serviço<select name="service_id" required><option value="">Selecione</option>\${services.filter(x=>x.active).map(x=>\`<option value="\${x.id}">\${esc(x.name)}</option>\`).join('')}</select></label><label>Data/hora<input name="starts_at" type="datetime-local" required></label><label>Status<select name="status"><option value="agendado">Agendado</option><option value="confirmado">Confirmado</option><option value="concluido">Concluído</option><option value="cancelado">Cancelado</option></select></label><div class="wide"><button class="action">Agendar</button></div></form><div id="msg" class="msg"></div>
  <table><tr><th>Data</th><th>Cliente</th><th>Profissional</th><th>Serviço</th><th>Status</th><th></th></tr>\${rows.map(r=>\`<tr><td>\${esc(r.starts_at.replace('T',' '))}</td><td>\${esc(r.client_name)}</td><td>\${esc(r.professional_name)}</td><td>\${esc(r.service_name)}</td><td>\${esc(r.status)}</td><td><button class="action danger" data-del-appt="\${r.id}">Excluir</button></td></tr>\`).join('')||'<tr><td colspan="6" class="empty">Nenhum agendamento.</td></tr>'}</table></div>\`;
  f.onsubmit=async e=>{e.preventDefault();try{await api('/api/appointments',{method:'POST',body:JSON.stringify(formData(f))});agenda()}catch(err){msg.textContent=err.message}};
  document.querySelectorAll('[data-del-appt]').forEach(b=>b.onclick=async()=>{await api('/api/appointments/'+b.dataset.delAppt,{method:'DELETE'});agenda()});
}
const views={dashboard,clientes,profissionais,servicos,agenda};
async function show(name){document.querySelectorAll('nav button').forEach(b=>b.classList.toggle('active',b.dataset.view===name));title.textContent={dashboard:'Visão geral',clientes:'Clientes',profissionais:'Profissionais',servicos:'Serviços',agenda:'Agenda'}[name];await views[name]();}
document.querySelectorAll('nav button').forEach(b=>b.onclick=()=>show(b.dataset.view));show('dashboard');
</script>
</body>
</html>`;
}

function scoped(rows, companyId) {
  return rows.filter(row => Number(row.company_id) === Number(companyId));
}

function enrichAppointments(store, companyId) {
  return scoped(store.appointments, companyId).map(a => ({
    ...a,
    client_name: store.clients.find(x => x.id === a.client_id && x.company_id === companyId)?.name || '',
    professional_name: store.professionals.find(x => x.id === a.professional_id && x.company_id === companyId)?.name || '',
    service_name: store.services.find(x => x.id === a.service_id && x.company_id === companyId)?.name || ''
  })).sort((a,b) => String(a.starts_at).localeCompare(String(b.starts_at)));
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, 'http://localhost');
  const store = load();
  const companyId = companyIdFrom(req, url);

  try {
    if (url.pathname === '/health') {
      return json(res, 200, { ok: true, service: 'plataforma-modular', version: '0.2.0', modules: ['base','clientes','profissionais','servicos','agenda'] });
    }

    if (url.pathname === '/') {
      res.writeHead(200, { 'content-type': 'text/html; charset=utf-8', 'cache-control': 'no-store' });
      return res.end(appHtml());
    }

    if (!url.pathname.startsWith('/api/')) return json(res, 404, { error: 'not_found' });

    const company = requireCompany(store, companyId, res);
    if (!company) return;

    if (url.pathname === '/api/modules' && req.method === 'GET') return json(res, 200, company);

    if (url.pathname === '/api/dashboard' && req.method === 'GET') {
      return json(res, 200, {
        company,
        counts: {
          clients: scoped(store.clients, companyId).length,
          professionals: scoped(store.professionals, companyId).length,
          services: scoped(store.services, companyId).length,
          appointments: scoped(store.appointments, companyId).length
        }
      });
    }

    const routes = [
      { base: '/api/clients', module: 'clientes', key: 'clients' },
      { base: '/api/professionals', module: 'profissionais', key: 'professionals' },
      { base: '/api/services', module: 'servicos', key: 'services' }
    ];

    for (const route of routes) {
      if (url.pathname === route.base && req.method === 'GET') {
        if (!requireModule(company, route.module, res)) return;
        return json(res, 200, scoped(store[route.key], companyId));
      }

      if (url.pathname === route.base && req.method === 'POST') {
        if (!requireModule(company, route.module, res)) return;
        const body = await readBody(req);
        if (!clean(body.name)) return json(res, 400, { error: 'nome_obrigatorio' });

        let row = { id: nextId(store[route.key]), company_id: companyId, name: clean(body.name), created_at: new Date().toISOString() };
        if (route.key === 'clients') row = { ...row, phone: clean(body.phone), email: clean(body.email), notes: clean(body.notes) };
        if (route.key === 'professionals') row = { ...row, phone: clean(body.phone), specialty: clean(body.specialty), active: String(body.active) !== 'false' };
        if (route.key === 'services') {
          const duration = Number(body.duration_minutes || 30);
          const price = Number(body.price || 0);
          if (!Number.isFinite(duration) || duration < 5 || !Number.isFinite(price) || price < 0) return json(res, 400, { error: 'dados_servico_invalidos' });
          row = { ...row, duration_minutes: duration, price, active: String(body.active) !== 'false' };
        }
        store[route.key].push(row);
        save(store);
        return json(res, 201, row);
      }

      const match = url.pathname.match(new RegExp('^' + route.base + '/(\\d+)$'));
      if (match && req.method === 'DELETE') {
        if (!requireModule(company, route.module, res)) return;
        const id = Number(match[1]);
        if (route.key === 'clients' && store.appointments.some(a => a.company_id === companyId && a.client_id === id)) return json(res, 409, { error: 'cliente_possui_agendamentos' });
        if (route.key === 'professionals' && store.appointments.some(a => a.company_id === companyId && a.professional_id === id)) return json(res, 409, { error: 'profissional_possui_agendamentos' });
        if (route.key === 'services' && store.appointments.some(a => a.company_id === companyId && a.service_id === id)) return json(res, 409, { error: 'servico_possui_agendamentos' });
        const before = store[route.key].length;
        store[route.key] = store[route.key].filter(row => !(row.id === id && row.company_id === companyId));
        if (before === store[route.key].length) return json(res, 404, { error: 'registro_nao_encontrado' });
        save(store);
        return json(res, 200, { ok: true });
      }
    }

    if (url.pathname === '/api/appointments' && req.method === 'GET') {
      if (!requireModule(company, 'agenda', res)) return;
      return json(res, 200, enrichAppointments(store, companyId));
    }

    if (url.pathname === '/api/appointments' && req.method === 'POST') {
      if (!requireModule(company, 'agenda', res)) return;
      const body = await readBody(req);
      const clientId = Number(body.client_id), professionalId = Number(body.professional_id), serviceId = Number(body.service_id);
      const startsAt = clean(body.starts_at);
      if (!clientId || !professionalId || !serviceId || !startsAt) return json(res, 400, { error: 'dados_agendamento_obrigatorios' });

      const client = store.clients.find(x => x.id === clientId && x.company_id === companyId);
      const professional = store.professionals.find(x => x.id === professionalId && x.company_id === companyId && x.active);
      const service = store.services.find(x => x.id === serviceId && x.company_id === companyId && x.active);
      if (!client || !professional || !service) return json(res, 400, { error: 'vinculo_agendamento_invalido' });

      const conflict = store.appointments.some(a => a.company_id === companyId && a.professional_id === professionalId && a.starts_at === startsAt && a.status !== 'cancelado');
      if (conflict) return json(res, 409, { error: 'horario_indisponivel' });

      const row = {
        id: nextId(store.appointments),
        company_id: companyId,
        client_id: clientId,
        professional_id: professionalId,
        service_id: serviceId,
        starts_at: startsAt,
        status: ['agendado','confirmado','concluido','cancelado'].includes(clean(body.status)) ? clean(body.status) : 'agendado',
        created_at: new Date().toISOString()
      };
      store.appointments.push(row);
      save(store);
      return json(res, 201, row);
    }

    const appointmentMatch = url.pathname.match(/^\/api\/appointments\/(\d+)$/);
    if (appointmentMatch && req.method === 'DELETE') {
      if (!requireModule(company, 'agenda', res)) return;
      const id = Number(appointmentMatch[1]);
      const before = store.appointments.length;
      store.appointments = store.appointments.filter(row => !(row.id === id && row.company_id === companyId));
      if (before === store.appointments.length) return json(res, 404, { error: 'agendamento_nao_encontrado' });
      save(store);
      return json(res, 200, { ok: true });
    }

    return json(res, 404, { error: 'not_found' });
  } catch (error) {
    return json(res, 500, { error: 'internal_error', detail: error.message });
  }
});

server.listen(PORT, '0.0.0.0', () => console.log('Plataforma Modular MVP 0.2.0 running on ' + PORT));
