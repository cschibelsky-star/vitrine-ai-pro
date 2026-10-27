"""External operator simulations. These fixtures are never MCP tools."""
import json
import os
from pathlib import Path
import subprocess
import sys
import unittest
from unittest.mock import patch
import test_lab
import test_security
from lab import canonical, rpc

class BudgetAuthorizationTests(unittest.TestCase):
    setUp = test_lab.LabTests.setUp
    tearDown = test_lab.LabTests.tearDown
    token = test_security.SecurityTests.token

    def args(self):
        return dict(purpose='test',budget_brl='0.3',ttl_seconds=60)

    def test_positive_budget_without_external_grant_denied(self):
        before=self.lab.db.execute('SELECT count(*) FROM runs').fetchone()[0]
        for value in ('', 'EXECUTAR', 'user-approved', 'forged.token'):
            with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
                with self.assertRaisesRegex(ValueError,'authorization'):
                    self.lab.call('run_create',**self.args(),approval=value)
        self.assertEqual(self.lab.db.execute('SELECT count(*) FROM runs').fetchone()[0],before)
        self.assertEqual(self.calls,[])

    def test_zero_budget_without_approval_has_no_paid_capacity(self):
        rid=self.lab.call('run_create',purpose='test',budget_brl='0',ttl_seconds=60,approval='')['run_id']
        with self.assertRaisesRegex(ValueError,'budget'):
            self.lab.call('chat_test',run_id=rid,provider='invision',model='demo',prompt='x',idempotency_key='x')
        self.assertEqual(self.calls,[])

    def test_budget_grant_is_single_use_and_audited(self):
        args=self.args();token=self.token(args,action='run_create')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            rid=self.lab.call('run_create',**args,approval=token)['run_id']
            with self.assertRaises(ValueError):self.lab.call('run_create',**args,approval=token)
        row=self.lab.db.execute('SELECT action,authorization_ref,instance_id,run_id,args_hash FROM authorization_audit WHERE nonce=?',('fixture',)).fetchone()
        self.assertEqual(row[:4],('run_create','external-fixture',self.config['instance_id'],rid))
        self.assertNotIn(token,canonical(row))

    def test_budget_and_ttl_cannot_be_increased(self):
        args=self.args();token=self.token(args,action='run_create')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            for changed in (dict(args,budget_brl='0.4'),dict(args,ttl_seconds=120)):
                with self.assertRaises(ValueError):self.lab.call('run_create',**changed,approval=token)
            self.lab.call('run_create',**args,approval=token)

    def test_budget_token_cannot_authorize_exception(self):
        args=self.args();token=self.token(args,action='run_create')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):
                self.lab.call('run_evidence',run_id=self.rid,purpose='exception',approval=token)
        self.assertEqual(self.lab.db.execute('SELECT count(*) FROM grants WHERE nonce=?',('fixture',)).fetchone()[0],0)

    def test_other_installation_rejected(self):
        args=self.args();token=self.token(args,action='run_create')
        self.config['instance_id']='hml-fixture-two'
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):self.lab.call('run_create',**args,approval=token)

    def test_missing_instance_or_key_denies_even_valid_signature(self):
        args=self.args();token=self.token(args,action='run_create')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':''}):
            with self.assertRaises(ValueError):self.lab.call('run_create',**args,approval=token)
        self.config.pop('instance_id')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            with self.assertRaises(ValueError):self.lab.call('run_create',**args,approval=token)

    def test_rpc_cannot_self_approve_budget(self):
        result=rpc(self.lab,{'id':1,'method':'tools/call','params':{'name':'run_create','arguments':dict(self.args(),approval='')}})
        self.assertTrue(result['result']['isError'])

    def test_offline_operator_cli_can_issue_exact_budget_grant(self):
        args=self.args();f=Path(self.temp.name)/'args.json';f.write_text(json.dumps(args))
        result=subprocess.run([sys.executable,'approve.py','--action','run_create','--args-file',str(f),'--authorization-ref','external-cli-fixture'],
            env={'PATH':os.defpath,'LAB_APPROVAL_SECRET':'a'*32,'LAB_INSTANCE_ID':self.config['instance_id']},
            capture_output=True,text=True,timeout=5)
        self.assertEqual(result.returncode,0)
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            self.assertIn('run_id',self.lab.call('run_create',**args,approval=result.stdout.strip()))

    def test_budget_grant_replay_after_restart_denied(self):
        from lab import Lab
        args=self.args();token=self.token(args,action='run_create')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            self.lab.call('run_create',**args,approval=token)
            self.lab.db.close();self.lab=Lab(self.temp.name+'/db',self.config,lambda *a:self.fail('unexpected transport'))
            with self.assertRaises(ValueError):self.lab.call('run_create',**args,approval=token)

    def test_concurrent_budget_grant_consumers_only_one_run(self):
        import threading
        from lab import Lab
        args=self.args();token=self.token(args,action='run_create')
        barrier=threading.Barrier(2);outcomes=[]
        def consume():
            lab=Lab(self.temp.name+'/db',self.config,lambda *a:self.fail('unexpected transport'))
            try:
                barrier.wait(timeout=5)
                try:lab.call('run_create',**args,approval=token);outcomes.append('ok')
                except ValueError:outcomes.append('denied')
            finally:lab.db.close()
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            workers=[threading.Thread(target=consume) for _ in range(2)]
            for worker in workers:worker.start()
            for worker in workers:worker.join(timeout=10);self.assertFalse(worker.is_alive())
        self.assertCountEqual(outcomes,['ok','denied'])

    def test_exception_audit_has_scope_reference_without_token(self):
        args=dict(run_id=self.rid,purpose='exception')
        token=self.token(args,action='run_evidence')
        with patch.dict(os.environ,{'LAB_APPROVAL_SECRET':'a'*32}):
            self.lab.call('run_evidence',**args,approval=token)
        row=self.lab.db.execute('SELECT * FROM authorization_audit WHERE nonce=?',('fixture',)).fetchone()
        self.assertEqual(row[1],'run_evidence');self.assertEqual(row[5],self.rid)
        self.assertNotIn(token,canonical(row))
