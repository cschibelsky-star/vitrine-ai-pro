"""Negative admission tests; actual container isolation is checked by CI."""
from contextlib import ExitStack
import unittest
from unittest.mock import patch
import container_hml


class ContainerAdmissionTests(unittest.TestCase):
    def guards(self, uid=65532, capabilities='0000000000000000', nnp='1', readonly=True,
               interfaces=None, credentials=None):
        status='CapEff:\t'+capabilities+'\nNoNewPrivs:\t'+nnp+'\n'
        mounts='overlay / overlay '+('ro' if readonly else 'rw')+',relatime 0 0\n'
        def read(path, *args, **kwargs):
            return {'/proc/self/status':status, '/proc/mounts':mounts}[str(path)]
        with ExitStack() as stack:
            stack.enter_context(patch('container_hml.os.geteuid',return_value=uid))
            stack.enter_context(patch('container_hml.Path.read_text',read))
            stack.enter_context(patch('container_hml.os.listdir',return_value=['lo'] if interfaces is None else interfaces))
            stack.enter_context(patch.dict('container_hml.os.environ',credentials or {},clear=True))
            container_hml.validate_container()

    def test_accepts_required_container_guards(self):self.guards()
    def test_rejects_root(self):
        with self.assertRaisesRegex(RuntimeError,'nonroot'):self.guards(uid=0)
    def test_rejects_capabilities(self):
        with self.assertRaisesRegex(RuntimeError,'capabilities'):self.guards(capabilities='0000000000000001')
    def test_rejects_privilege_escalation(self):
        with self.assertRaisesRegex(RuntimeError,'no_new_privileges'):self.guards(nnp='0')
    def test_rejects_writable_root(self):
        with self.assertRaisesRegex(RuntimeError,'readonly'):self.guards(readonly=False)
    def test_rejects_external_interface(self):
        with self.assertRaisesRegex(RuntimeError,'loopback'):self.guards(interfaces=['lo','eth0'])
    def test_rejects_credentials_before_suite(self):
        for name in container_hml.CREDENTIALS:
            with self.subTest(name=name):
                with self.assertRaisesRegex(RuntimeError,'credentials'):
                    self.guards(credentials={name:'synthetic-forbidden-fixture'})
