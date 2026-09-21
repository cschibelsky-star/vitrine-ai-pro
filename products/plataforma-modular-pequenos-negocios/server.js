import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';

const PORT=8788;
const dataDir='/app/data';
const dataFile=path.join(dataDir,'store.json');
fs.mkdirSync(dataDir,{recursive:true});

const defaultStore={
  companies:[{id:1,name:'Empresa Demonstração',modules:['base','clientes','profissionais','servicos','agenda']}],
  clients:[],
  professionals:[],
  services:[],
  appointments:[]
};
function load(){try{return JSON.parse(fs.readFileSync(dataFile,'utf8'));}catch{return structuredClone(defaultStore);}}
function save(s){fs.writeFileSync(dataFile,JSON.stringify(s,null,2));}
if(!fs.existsSync(dataFile)) save(defaultStore);

const labels={clientes:'Clientes',profissionais:'Profissionais',servicos:'Serviços',agenda:'Agenda'};
const html=()=>`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Plataforma Modular</title><style>
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui;background:#f5f7fb;color:#172033}.shell{display:grid;grid-template-columns:250px 1fr;min-height:100vh}aside{background:#101827;color:#fff;padding:28px 20px}.brand{font-size:22px;font-weight:800}.brand small{display:block;font-size:12px;font-weight:500;color:#9fb0c8;margin-top:5px}nav{margin-top:30px}nav a{display:block;padding:12px 14px;margin:5px 0;border-radius:10px;color:#d9e2ef;text-decoration:none}nav a:hover{background:#1d2a3d}main{padding:34px}.eyebrow{font-size:12px;font-weight:800;text-transform:uppercase;color:#667085}h1{margin:8px 0 6px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}.card{background:#fff;border:1px solid #e5e9f0;border-radius:18px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.04)}.card b{font-size:28px;display:block;margin-top:8px}.modules{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:20px}.module{background:#fff;padding:22px;border-radius:18px;border:1px solid #e5e9f0}.pill{font-size:12px;padding:5px 9px;background:#e9f9ef;color:#16794a;border-radius:999px}.future{opacity:.58}@media(max-width:850px){.shell{grid-template-columns:1fr}aside{display:none}.grid{grid-template-columns:1fr 1fr}.modules{grid-template-columns:1fr}}
</style></head><body><div class="shell"><aside><div class="brand">Plataforma Modular<small>Pequenos Negócios</small></div><nav><a href="/">Visão geral</a><a href="/?m=clientes">Clientes</a><a href="/?m=profissionais">Profissionais</a><a href="/?m=servicos">Serviços</a><a href="/?m=agenda">Agenda</a></nav></aside><main><span class="eyebrow">MVP • Ambiente de homologação</span><h1>Seu negócio, só com os módulos que precisa.</h1><p>Base multiempresa independente da Gestão de Cadastro.</p><div class="grid"><div class="card">Clientes<b>0</b></div><div class="card">Profissionais<b>0</b></div><div class="card">Serviços<b>0</b></div><div class="card">Agendamentos<b>0</b></div></div><h2>Módulos</h2><div class="modules">${Object.entries(labels).map(([k,v])=>`<div class="module"><span class="pill">ATIVO</span><h3>${v}</h3><p>Módulo habilitado para esta empresa.</p></div>`).join('')}<div class="module future"><span class="pill">ROADMAP</span><h3>Financeiro · Caixa · Estoque · Comissões · PDV · Marketing</h3><p>Expansão modular sem alterar o núcleo do cliente.</p></div></div></main></div></body></html>`;

const server=http.createServer((req,res)=>{
 if(req.url==='/health'){res.writeHead(200,{'content-type':'application/json'});return res.end(JSON.stringify({ok:true,service:'plataforma-modular',version:'0.1.0',modules:['base','clientes','profissionais','servicos','agenda']}));}
 if(req.url==='/api/modules'){const s=load();res.writeHead(200,{'content-type':'application/json'});return res.end(JSON.stringify(s.companies[0]));}
 res.writeHead(200,{'content-type':'text/html; charset=utf-8'});res.end(html());
});
server.listen(PORT,'0.0.0.0',()=>console.log('Plataforma Modular MVP running on '+PORT));
