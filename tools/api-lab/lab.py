"""Vitrine API Lab: isolated stdio MCP pilot; Python standard library only."""
import hashlib
import hmac
import http.client
import ipaddress
import json
import os
import secrets
import socket
import sqlite3
import ssl
import sys
import time
from decimal import Decimal
from pathlib import Path

PROVIDERS = {
    'invision': ('invision.dreamvision.com.br', '/v1', 'INVISION_API_KEY'),
    'roteia': ('api.roteia.ai', '/v1', 'ROTEIA_API_KEY'),
    'openrouter': ('openrouter.ai', '/api/v1', 'OPENROUTER_API_KEY'),
}

def canonical(value):
    return json.dumps(value, sort_keys=True, separators=(',', ':'), ensure_ascii=False)

def amount(value):
    n = Decimal(str(value))
    if not n.is_finite() or n < 0 or n.as_tuple().exponent < -6:
        raise ValueError('invalid_amount')
    return int(n * 1000000)

class PinnedHTTPS(http.client.HTTPSConnection):
    def connect(self):
        addresses = socket.getaddrinfo(self.host, 443, type=socket.SOCK_STREAM)
        ips = [a[4][0] for a in addresses]
        if not ips or any(not ipaddress.ip_address(ip).is_global for ip in ips):
            raise ValueError('destination_not_public')
        raw = socket.create_connection((ips[0], 443), self.timeout)
        try:
            self.sock = self._context.wrap_socket(raw, server_hostname=self.host)
        except Exception:
            raw.close()
            raise

def request(provider, path, payload=None):
    host, base, keyname = PROVIDERS[provider]
    key = os.environ.get(keyname)
    if not key:
        raise ValueError('credential_absent')
    conn = PinnedHTTPS(host, timeout=30, context=ssl.create_default_context())
    try:
        conn.request('GET' if payload is None else 'POST', base + path,
                     body=None if payload is None else canonical(payload).encode(),
                     headers={'Authorization': 'Bearer ' + key, 'Content-Type': 'application/json'})
        response = conn.getresponse()
        body = response.read(2_000_001)
        if len(body) > 2_000_000:
            raise ValueError('response_too_large')
        # Never return vendor error bodies: they can echo prompts or credentials.
        if response.status != 200:
            return {'http_status': response.status, 'error': 'provider_rejected_request'}
        data = json.loads(body)
        clean = canonical(data)
        for name in [p[2] for p in PROVIDERS.values()]:
            secret = os.environ.get(name)
            if secret:
                clean = clean.replace(secret, '[REDACTED]')
        return {'http_status': 200, 'data': json.loads(clean)}
    finally:
        conn.close()

class Lab:
    def __init__(self, database, config, transport=request, threaded=False):
        self.config, self.transport = config, transport
        self.db = sqlite3.connect(database, isolation_level=None, timeout=10, check_same_thread=not threaded)
        self.db.execute('PRAGMA journal_mode=WAL')
        self.db.executescript('''
        CREATE TABLE IF NOT EXISTS runs(id TEXT PRIMARY KEY, purpose TEXT, expires REAL, budget INTEGER, reserved INTEGER DEFAULT 0);
        CREATE TABLE IF NOT EXISTS attempts(id TEXT PRIMARY KEY, run TEXT, idem TEXT, fingerprint TEXT, reserve INTEGER, state TEXT, evidence TEXT, UNIQUE(run,idem));
        CREATE TABLE IF NOT EXISTS grants(nonce TEXT PRIMARY KEY);
        CREATE TABLE IF NOT EXISTS artifacts(run TEXT, name TEXT, content TEXT, PRIMARY KEY(run,name));
        ''')

    def grant(self, action, args, token):
        """Verify operator-issued grant, never exposed as MCP minting tool."""
        key = os.environ.get('LAB_APPROVAL_SECRET', '')
        try:
            body, signature = token.rsplit('.', 1)
            obj = json.loads(bytes.fromhex(body))
            expected = hmac.new(key.encode(), body.encode(), hashlib.sha256).hexdigest()
            valid = (len(key) >= 32 and hmac.compare_digest(expected, signature)
                and obj['action'] == action and obj['hash'] == hashlib.sha256(canonical(args).encode()).hexdigest()
                and time.time() < obj['expires'] <= time.time() + 900
                and obj.get('authorization_ref') and obj.get('nonce'))
            if not valid:
                raise ValueError()
            self.db.execute('INSERT INTO grants VALUES(?)', (obj['nonce'],))
        except Exception:
            raise ValueError('specific_operator_authorization_required') from None

    def run(self, run_id):
        row = self.db.execute('SELECT purpose,expires,budget,reserved FROM runs WHERE id=?', (run_id,)).fetchone()
        if not row or row[1] <= time.time():
            raise ValueError('run_missing_or_expired')
        return row

    def call(self, action, **args):
        if action == 'lab_status':
            return {'mode': 'test_only', 'transports_implemented': ['stdio','http_loopback_single_operator'], 'implemented': ['catalog', 'chat', 'run_evidence', 'artifact_write'],
                    'pending': ['voice', 'image', 'video', 'tls_remote_deployment', 'cockpit_approval'],
                    'credentials': {p: bool(os.environ.get(v[2])) for p, v in PROVIDERS.items()}}
        if action == 'run_create':
            if set(args) != {'purpose', 'budget_brl', 'ttl_seconds', 'approval'}:
                raise ValueError('invalid_arguments')
            purpose, ttl = args['purpose'], args['ttl_seconds']
            if purpose not in ('test', 'exception') or type(ttl) is not int or not 1 <= ttl <= 3600:
                raise ValueError('invalid_run')
            budget = amount(args['budget_brl'])
            if budget > amount(self.config['max_run_brl']):
                raise ValueError('run_budget_exceeded')
            if purpose == 'exception':
                raise ValueError('exception_requires_per_call_grant_not_unrestricted_run')
            rid = secrets.token_hex(16)
            self.db.execute('INSERT INTO runs(id,purpose,expires,budget) VALUES(?,?,?,?)', (rid, purpose, time.time()+ttl, budget))
            return {'run_id': rid}
        rid = args.get('run_id')
        row = self.run(rid)
        purpose = args.get('purpose', 'test')
        if purpose != 'test':
            scoped = {k: v for k, v in args.items() if k != 'approval'}
            self.grant(action, scoped, args.get('approval', ''))
        if action == 'artifact_write':
            name, content = args['name'], args['content']
            if not isinstance(name, str) or not name or len(name)>100 or '/' in name or '\\' in name or '..' in name:
                raise ValueError('invalid_artifact_name')
            if not isinstance(content, str) or len(content.encode()) > 100000:
                raise ValueError('artifact_too_large')
            if self.db.execute('SELECT count(*) FROM artifacts WHERE run=?', (rid,)).fetchone()[0] >= 20:
                raise ValueError('artifact_limit')
            for env in [x[2] for x in PROVIDERS.values()] + ['LAB_APPROVAL_SECRET']:
                secret = os.environ.get(env)
                if secret:
                    content = content.replace(secret, '[REDACTED]')
            self.db.execute('INSERT INTO artifacts VALUES(?,?,?) ON CONFLICT(run,name) DO UPDATE SET content=excluded.content', (rid,name,content))
            return {'written': name, 'run_id': rid}
        if action == 'run_evidence':
            return {'run_id':rid,'budget_brl':str(Decimal(row[2])/1000000),
                    'reserved_brl':str(Decimal(row[3])/1000000), 'cost_basis':'conservative_reserve_not_verified_debit',
                    'attempts':[json.loads(x[0]) for x in self.db.execute('SELECT evidence FROM attempts WHERE run=? AND evidence IS NOT NULL',(rid,))],
                    'artifacts':[{'name':x[0],'content':x[1]} for x in self.db.execute('SELECT name,content FROM artifacts WHERE run=?',(rid,))]}
        if action not in ('catalog_read','chat_test'):
            raise ValueError('operation_not_allowed')
        provider = args['provider']
        if provider not in self.config['providers'] or provider not in PROVIDERS:
            raise ValueError('provider_not_allowed')
        if action == 'catalog_read':
            data=self.transport(provider,'/models')
            if data.get('http_status')!=200:
                return {'http_status':data.get('http_status'),'error':'catalog_unavailable'}
            return {'models':[{'id':x.get('id')} for x in data['data'].get('data',[])][:1000]}
        model, prompt, idem = args['model'], args['prompt'], args['idempotency_key']
        settings = self.config['providers'][provider]['models'].get(model)
        if not settings or not isinstance(prompt,str) or not 1<=len(prompt.encode())<=4000:
            raise ValueError('model_or_prompt_not_allowed')
        if not isinstance(idem,str) or not 1<=len(idem)<=100:
            raise ValueError('invalid_idempotency_key')
        # Operator must certify a conservative bound for THIS bounded payload and account.
        if not settings.get('ceiling_verified') or not settings.get('price_source'):
            raise ValueError('verified_cost_ceiling_required')
        reserve=amount(settings['max_charge_brl'])
        if reserve <= 0:
            raise ValueError('positive_reserve_required')
        payload={'model':model,'messages':[{'role':'user','content':prompt}], 'max_tokens':128,'stream':False}
        fingerprint=hashlib.sha256(canonical(payload).encode()+provider.encode()).hexdigest()
        aid=secrets.token_hex(16)
        self.db.execute('BEGIN IMMEDIATE')
        try:
            previous=self.db.execute('SELECT fingerprint,evidence,state FROM attempts WHERE run=? AND idem=?',(rid,idem)).fetchone()
            if previous:
                if previous[0]!=fingerprint: raise ValueError('idempotency_conflict')
                self.db.execute('COMMIT')
                return json.loads(previous[1]) if previous[1] else {'state':previous[2], 'retry_forbidden':True}
            row=self.run(rid)
            if row[3]+reserve>row[2]: raise ValueError('budget_exceeded')
            total=self.db.execute('SELECT COALESCE(SUM(reserved),0) FROM runs').fetchone()[0]
            if total+reserve>amount(self.config['max_total_brl']):raise ValueError('global_budget_exceeded')
            self.db.execute('UPDATE runs SET reserved=reserved+? WHERE id=?',(reserve,rid))
            self.db.execute('INSERT INTO attempts VALUES(?,?,?,?,?,?,?)',(aid,rid,idem,fingerprint,reserve,'in_flight',None))
            self.db.execute('COMMIT')
        except Exception:
            if self.db.in_transaction:self.db.execute('ROLLBACK')
            raise
        start=time.monotonic()
        try:
            result=self.transport(provider,'/chat/completions',payload)
            d=result.get('data',{})
            choices=d.get('choices',[])
            result={'http_status':result.get('http_status'), 'model_returned':d.get('model'),
                    'output':choices[0].get('message',{}).get('content') if choices else None,
                    'usage':d.get('usage'), 'error':result.get('error')}
        except Exception:
            result={'http_status':None,'error':'transport_or_parse_failure','billing_unknown':True}
        result.update(attempt_id=aid,run_id=rid,provider=provider,model_requested=model,
                      input_hash=hashlib.sha256(prompt.encode()).hexdigest(), total_ms=round((time.monotonic()-start)*1000),
                      reserve_brl=str(Decimal(reserve)/1000000), actual_cost_brl=None, quality='not_evaluated')
        # Full reserve retained even on failure. Never claim this equals actual spend.
        self.db.execute('UPDATE attempts SET state=?,evidence=? WHERE id=?',('finished',canonical(result),aid))
        return result

SCHEMAS = {
 'lab_status':{},
 'run_create':{'purpose':{'enum':['test']},'budget_brl':{'type':'string'},'ttl_seconds':{'type':'integer'},'approval':{'type':'string'}},
 'catalog_read':{'run_id':{'type':'string'},'provider':{'enum':list(PROVIDERS)}},
 'chat_test':{'run_id':{'type':'string'},'provider':{'enum':list(PROVIDERS)},'model':{'type':'string'},'prompt':{'type':'string'},'idempotency_key':{'type':'string'}},
 'artifact_write':{'run_id':{'type':'string'},'name':{'type':'string'},'content':{'type':'string'}},
 'run_evidence':{'run_id':{'type':'string'}},
}

REQUIRED = {name:list(props) for name,props in SCHEMAS.items()}
for name, props in SCHEMAS.items():
    if name not in ('lab_status', 'run_create'):
        props.update(purpose={'enum':['test','exception']}, approval={'type':'string'})

def rpc(lab, msg):
    method=msg.get('method'); ident=msg.get('id')
    if ident is None: return None
    result=None
    if method=='initialize':
        version=msg.get('params',{}).get('protocolVersion')
        result={'protocolVersion':version if version in ('2024-11-05','2025-03-26','2025-06-18') else '2025-03-26',
                'capabilities':{'tools':{}},'serverInfo':{'name':'vitrine-api-lab','version':'0.1.0'}}
    elif method=='ping': result={}
    elif method=='tools/list':
        result={'tools':[{'name':name,'description':'Isolated laboratory operation: '+name,'inputSchema':{'type':'object','properties':props,'required':REQUIRED[name],'additionalProperties':False}} for name,props in SCHEMAS.items()]}
    elif method=='tools/call':
        p=msg.get('params',{}); name=p.get('name'); args=p.get('arguments',{})
        try:
            if name not in SCHEMAS or not isinstance(args,dict) or (not set(REQUIRED[name]).issubset(args) or not set(args).issubset(SCHEMAS[name])):
                raise ValueError('invalid_tool_arguments')
            value=lab.call(name,**args)
            result={'content':[{'type':'text','text':canonical(value)}]}
        except Exception:
            # Do not include exception text from provider or runtime.
            result={'isError':True,'content':[{'type':'text','text':'Operation denied or failed; inspect configured scope, budget and run status.'}]}
    else:return {'jsonrpc':'2.0','id':ident,'error':{'code':-32601,'message':'Method not found'}}
    return {'jsonrpc':'2.0','id':ident,'result':result}

if __name__=='__main__':
    os.umask(0o077)
    config=json.loads(Path(os.environ['LAB_CONFIG']).read_text())
    lab=Lab(os.environ['LAB_DATABASE'],config)
    for line in sys.stdin:
        try:
            if len(line)>100000:raise ValueError()
            response=rpc(lab,json.loads(line))
        except Exception:
            response={'jsonrpc':'2.0','id':None,'error':{'code':-32700,'message':'Invalid request'}}
        if response is not None: print(canonical(response),flush=True)
