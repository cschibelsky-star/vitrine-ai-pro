"""Offline security regressions; synthetic keys and transport only."""
import hashlib
import hmac
import os
import time
from unittest.mock import patch
import unittest
import test_lab
from lab import Lab, canonical, rpc

class SecurityTests(unittest.TestCase):
    setUp = test_lab.LabTests.setUp
    tearDown = test_lab.LabTests.tearDown
    chat = test_lab.LabTests.chat
    def token(self, args, action='chat_test', expires=None, nonce='fixture', ref='external-fixture'):
        body=canonical(dict(instance_id=self.config['instance_id'], action=action, hash=hashlib.sha256(canonical(args).encode()).hexdigest(),
            expires=time.time()+120 if expires is None else expires, nonce=nonce, authorization_ref=ref)).encode().hex()
        return body+'.'+hmac.new(b'a'*32,body.encode(),hashlib.sha256).hexdigest()

    def args(self):
        return dict(run_id=self.rid,provider='invision',model='demo',prompt='Hello',idempotency_key='a',purpose='exception')

    def test_signed_unknown_purpose_denied(self):
        args=self.args();args['purpose']='production'
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):self.lab.call('chat_test',**args,approval=self.token(args))
        self.assertEqual(self.calls,[])

    def test_price_verification_must_be_boolean(self):
        self.config['providers']['invision']['models']['demo']['ceiling_verified']='false'
        with self.assertRaises(ValueError):self.chat()
        self.assertEqual(self.calls,[])

    def test_direct_unknown_argument_denied(self):
        with self.assertRaises(ValueError):self.chat(environment='production')
        self.assertEqual(self.calls,[])

    def test_secrets_redacted_from_artifact(self):
        with patch.dict(os.environ,{'LAB_ACCESS_TOKEN':'access-fixture'}):
            self.lab.call('artifact_write',run_id=self.rid,name='x',content='access-fixture')
            self.assertNotIn('access-fixture',canonical(self.lab.call('run_evidence',run_id=self.rid)))

    def test_invalid_grants_do_not_call_transport(self):
        args=self.args()
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            tokens=[self.token(args,expires=time.time()-1),self.token(args,action='catalog_read'),
                    self.token(args,ref=''),self.token(args)+ '0']
            changed=dict(args,prompt='Changed')
            tokens.append(self.token(changed))
            for token in tokens:
                with self.subTest(token_kind=tokens.index(token)):
                    with self.assertRaises(ValueError):self.lab.call('chat_test',**args,approval=token)
        self.assertEqual(self.calls,[])

    def test_grant_replay_after_restart_denied(self):
        args=self.args();token=self.token(args)
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            self.lab.call('chat_test',**args,approval=token)
            self.lab.db.close();self.lab=Lab(self.temp.name+'/db',self.config,lambda *a:self.fail('replayed'))
            with self.assertRaises(ValueError):self.lab.call('chat_test',**args,approval=token)

    def test_idempotency_after_restart(self):
        result=self.chat()
        self.lab.db.close();self.lab=Lab(self.temp.name+'/db',self.config,lambda *a:self.fail('duplicate'))
        self.assertEqual(result,self.chat())

    def test_timeout_retry_not_sent(self):
        calls=[]
        def fail(*a):calls.append(a);raise TimeoutError()
        self.lab.transport=fail
        result=self.chat();self.assertEqual(result,self.chat())
        self.assertEqual(len(calls),1)
        self.assertEqual(self.lab.call('run_evidence',run_id=self.rid)['reserved_brl'],'0.1')

    def test_expired_runs_keep_global_reserve(self):
        self.config['max_total_brl']='0.1';self.chat()
        self.lab.db.execute('UPDATE runs SET expires=0')
        self.rid=test_lab.create_run(self.lab,purpose='test',budget_brl='0.2',ttl_seconds=60,approval='')['run_id']
        with self.assertRaises(ValueError):self.chat()
        self.assertEqual(len(self.calls),1)

    def test_no_minting_or_operational_tool(self):
        names=[x['name'] for x in rpc(self.lab,{'id':1,'method':'tools/list'})['result']['tools']]
        self.assertEqual(set(names),{'lab_status','run_create','catalog_read','chat_test','artifact_write','run_evidence'})
        for name in ('approve','grant','deploy','shell','request'):
            self.assertTrue(rpc(self.lab,{'id':1,'method':'tools/call','params':{'name':name,'arguments':{}}})['result']['isError'])

    def test_other_namespace_not_returned(self):
        self.lab.call('artifact_write',run_id=self.rid,name='private',content='run-one')
        other=test_lab.create_run(self.lab,purpose='test',budget_brl='0',ttl_seconds=60,approval='')['run_id']
        self.assertEqual(self.lab.call('run_evidence',run_id=other)['artifacts'],[])

    def test_rpc_types_and_purpose_fail_closed(self):
        for extra in ({'purpose':'operational'}, {'purpose':None}, {'prompt':123}, {'idempotency_key':False}):
            args=dict(self.args(),purpose='test');args.update(extra)
            result=rpc(self.lab,{'id':1,'method':'tools/call','params':{'name':'chat_test','arguments':args}})
            self.assertTrue(result['result']['isError'])
        self.assertEqual(self.calls,[])

    def test_oversized_budget_rejected_before_storage(self):
        with self.assertRaises(ValueError):
            self.lab.call('run_create',purpose='test',budget_brl='1e100',ttl_seconds=60,approval='')

    def test_grant_does_not_override_budget(self):
        self.lab.db.execute('UPDATE runs SET budget=0')
        args=self.args()
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaisesRegex(ValueError,'budget'):
                self.lab.call('chat_test',**args,approval=self.token(args))
        self.assertEqual(self.calls,[])

    def test_concurrent_connections_reserve_atomically(self):
        import threading
        self.config['max_total_brl']='0.1'
        barrier=threading.Barrier(2);results=[];sent=[]
        def worker(idem):
            lab=Lab(self.temp.name+'/db',self.config,lambda *a:sent.append(a) or {'http_status':200,'data':{}})
            try:
                barrier.wait(timeout=5)
                try:
                    lab.call('chat_test',run_id=self.rid,provider='invision',model='demo',prompt='Hello',idempotency_key=idem)
                    results.append('ok')
                except ValueError:results.append('denied')
            finally:lab.db.close()
        threads=[threading.Thread(target=worker,args=(str(i),)) for i in range(2)]
        for t in threads:t.start()
        for t in threads:t.join(timeout=10);self.assertFalse(t.is_alive())
        self.assertCountEqual(results,['ok','denied']);self.assertEqual(len(sent),1)
        self.assertEqual(self.lab.call('run_evidence',run_id=self.rid)['reserved_brl'],'0.1')

    def test_grant_binds_every_operation_argument(self):
        args=self.args();token=self.token(args)
        other=test_lab.create_run(self.lab,purpose='test',budget_brl='0.2',ttl_seconds=60,approval='')['run_id']
        changes=[{'run_id':other},{'provider':'roteia'},{'model':'other'}, {'idempotency_key':'b'}]
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            for change in changes:
                with self.subTest(change=change):
                    with self.assertRaises(ValueError):self.lab.call('chat_test',**dict(args,**change),approval=token)
            self.lab.call('chat_test',**args,approval=token)
        self.assertEqual(len(self.calls),1)

    def test_grant_lifetime_no_more_than_five_minutes(self):
        args=self.args()
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):
                self.lab.call('chat_test',**args,approval=self.token(args,expires=time.time()+600))
        self.assertEqual(self.calls,[])
