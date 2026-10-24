#!/usr/bin/env node
'use strict';
// Read-only, non-destructive Assessor Online readiness probe.
// Run: node tests/Assessor/readiness.cjs https://assessor.hml.vitrineiapro.com.br
const base=(process.argv[2]||'https://assessor.hml.vitrineiapro.com.br').replace(/\/$/,'');
const checks=[
  {name:'HML demo availability',path:'/demo.html',expected:200,gate:'G0',informational:true},
  {name:'Service health',path:'/health',expected:200,gate:'G0',informational:true},
  {name:'Authenticated application entry',path:'/login',expected:200,gate:'G1'},
  {name:'Administrative application entry',path:'/admin',expected:200,gate:'G1'},
  {name:'API health endpoint',path:'/api/health',expected:200,gate:'G2'}
];
(async()=>{
 const results=[];
 for(const c of checks){
  try{
   const response=await fetch(base+c.path,{method:'GET',redirect:'manual',signal:AbortSignal.timeout(10000)});
   const ok=response.status===c.expected;
   results.push({...c,status:response.status,ok});
  }catch(error){results.push({...c,status:'ERROR',ok:false,error:error.message})}
 }
 for(const r of results) console.log((r.ok?'PASS':'FAIL')+' ['+r.gate+'] '+r.name+': '+r.status+' '+base+r.path);
 const blocking=results.filter(r=>!r.informational&&!r.ok);
 console.log(JSON.stringify({target:base,checked_at:new Date().toISOString(),readiness:'NOT_APPROVED',blocking_checks:blocking.length,results},null,2));
 console.log('HTTP checks alone NEVER approve commercial release; complete G0-G7 integration, security and payment tests.');
 if(blocking.length)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=2});
