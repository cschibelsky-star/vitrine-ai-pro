import hashlib
import hmac
import json
import os
import tempfile
import time
import unittest
from unittest.mock import patch
from lab import Lab, canonical, rpc, PinnedHTTPS

class LabTests(unittest.TestCase):
    def setUp(self):
        self.temp=tempfile.TemporaryDirectory()
        self.calls=[]
        self.config={'max_total_brl':'2','max_run_brl':'2','providers':{'invision':{'models':{'demo':{'max_charge_brl':'0.10','ceiling_verified':True,'price_source':'test fixture'}}}}}
        def transport(provider,path,payload=None):
            self.calls.append((provider,path,payload))
            return {'http_status':200,'data':{'model':'demo','choices':[{'message':{'content':'OK'}}],'usage':{'prompt_tokens':4,'completion_tokens':1}}}
        self.lab=Lab(self.temp.name+'/db',self.config,transport)
        self.rid=self.lab.call('run_create',purpose='test',budget_brl='0.20',ttl_seconds=60,approval='')['run_id']
    def tearDown(self):
        self.lab.db.close(); self.temp.cleanup()
    def chat(self,idem='a',**extra):
        return self.lab.call('chat_test',run_id=self.rid,provider='invision',model='demo',prompt='Hello',idempotency_key=idem,**extra)
    def test_chat_and_reserve_not_debit(self):
        r=self.chat();self.assertEqual(r['output'],'OK');self.assertIsNone(r['actual_cost_brl'])
        self.assertEqual(self.lab.call('run_evidence',run_id=self.rid)['reserved_brl'],'0.1')
    def test_budget(self):
        self.chat('a');self.chat('b')
        with self.assertRaisesRegex(ValueError,'budget'):self.chat('c')
        self.assertEqual(len(self.calls),2)
    def test_global_budget_across_runs(self):
        self.config['max_total_brl']='0.10'
        self.chat()
        self.rid=self.lab.call('run_create',purpose='test',budget_brl='0.20',ttl_seconds=60,approval='')['run_id']
        with self.assertRaisesRegex(ValueError,'global_budget'):self.chat()
        self.assertEqual(len(self.calls),1)
    def test_idempotency(self):
        self.assertEqual(self.chat(),self.chat());self.assertEqual(len(self.calls),1)
    def test_idempotency_conflict(self):
        self.chat()
        with self.assertRaisesRegex(ValueError,'conflict'):
            self.lab.call('chat_test',run_id=self.rid,provider='invision',model='demo',prompt='Different',idempotency_key='a')
    def test_no_run(self):
        with self.assertRaises(ValueError):self.lab.call('chat_test',run_id='none')
    def test_expiry(self):
        self.lab.db.execute('UPDATE runs SET expires=0')
        with self.assertRaises(ValueError):self.chat()
    def test_unknown_operation(self):
        with self.assertRaises(ValueError):self.lab.call('deploy',run_id=self.rid)
    def test_exception_needs_grant(self):
        with self.assertRaisesRegex(ValueError,'authorization'):self.chat(purpose='exception')
        self.assertEqual(self.calls,[])
    def test_specific_single_use_grant(self):
        args=dict(run_id=self.rid,provider='invision',model='demo',prompt='Hello',idempotency_key='a',purpose='exception')
        body={'action':'chat_test','hash':hashlib.sha256(canonical(args).encode()).hexdigest(),'expires':time.time()+120,'authorization_ref':'operator fixture','nonce':'unique'}
        encoded=canonical(body).encode().hex()
        secret='a'*32
        token=encoded+'.'+hmac.new(secret.encode(),encoded.encode(),hashlib.sha256).hexdigest()
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':secret}):
            self.lab.call('chat_test',**args,approval=token)
            with self.assertRaises(ValueError):self.lab.call('chat_test',**args,approval=token)
        self.assertEqual(len(self.calls),1)
    def test_grant_cannot_change_destination(self):
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):self.chat(purpose='exception',approval='bad')
    def test_no_global_exception_run(self):
        with self.assertRaises(ValueError):self.lab.call('run_create',purpose='exception',budget_brl='1',ttl_seconds=60,approval='')
    def test_artifact_isolation_and_redaction(self):
        with patch.dict(os.environ,{'INVISION_API_KEY':'secret-fixture'}):
            self.lab.call('artifact_write',run_id=self.rid,name='fixture',content='secret-fixture')
        r=self.lab.call('run_evidence',run_id=self.rid)
        self.assertEqual(r['artifacts'][0]['content'],'[REDACTED]')
        with self.assertRaises(ValueError):self.lab.call('artifact_write',run_id=self.rid,name='../bad',content='x')
    def test_unverified_price(self):
        self.config['providers']['invision']['models']['demo']['ceiling_verified']=False
        with self.assertRaises(ValueError):self.chat()
        self.assertFalse(self.calls)
    def test_transport_failure_retains_reserve(self):
        self.lab.transport=lambda *a: (_ for _ in ()).throw(TimeoutError('secret'))
        r=self.chat();self.assertTrue(r['billing_unknown']);self.assertNotIn('secret',canonical(r))
        self.assertEqual(self.lab.call('run_evidence',run_id=self.rid)['reserved_brl'],'0.1')
    def test_rpc_unknown_argument(self):
        r=rpc(self.lab,{'id':1,'method':'tools/call','params':{'name':'lab_status','arguments':{'shell':'id'}}})
        self.assertTrue(r['result']['isError'])
    def test_dns_private_denied(self):
        conn=PinnedHTTPS('example.com')
        with patch('socket.getaddrinfo',return_value=[(2,1,6,'',('127.0.0.1',443))]):
            with self.assertRaisesRegex(ValueError,'public'):conn.connect()
    def test_invalid_budget(self):
        for n in ['NaN','Infinity','-1','0.0000001']:
            with self.assertRaises(ValueError):self.lab.call('run_create',purpose='test',budget_brl=n,ttl_seconds=60,approval='')

if __name__=='__main__':unittest.main()
