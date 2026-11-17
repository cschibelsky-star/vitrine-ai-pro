import unittest
from mission import Mission, State
from avello_adapter import AvelloExecutionAdapter, AvelloError

def running():
    m = Mission('DEV work', 'DEV')
    m.transition(State.ASSIGNED, request_id='a', actor='operator')
    m.transition(State.EXECUTING, request_id='b', actor='agent')
    return m

class AvelloAdapterTests(unittest.TestCase):
    def test_disabled(self):
        with self.assertRaisesRegex(AvelloError, 'disabled'):
            AvelloExecutionAdapter().execute(running(), model='m', prompt='x', request_id='r', actor='agent')
    def test_valid_mock_receipt_does_not_approve(self):
        m = running()
        adapter = AvelloExecutionAdapter(enabled=True, transport=lambda req: {
            'status':'completed','provider':'avello','mission_id':req['mission_id'],
            'model':req['model'],'execution_id':'ext-1','evidence_ref':'artifact:1'})
        result = adapter.execute(m, model='m', prompt='work', request_id='r', actor='agent')
        self.assertEqual(result.provider, 'avello')
        self.assertEqual(m.state, State.EXECUTING)
        self.assertEqual(m.evidence, ['artifact:1'])
    def test_rejects_fake_provider(self):
        m = running()
        with self.assertRaisesRegex(AvelloError, 'identity_mismatch'):
            AvelloExecutionAdapter(enabled=True, transport=lambda req: {
                'status':'completed','provider':'other','mission_id':req['mission_id'],
                'model':'m','execution_id':'e','evidence_ref':'r'}).execute(
                m, model='m', prompt='x', request_id='r', actor='agent')
    def test_rejects_missing_evidence(self):
        m = running()
        with self.assertRaisesRegex(AvelloError, 'evidence_missing'):
            AvelloExecutionAdapter(enabled=True, transport=lambda req: {
                'status':'completed','provider':'avello','mission_id':req['mission_id'],
                'model':'m','execution_id':'e'}).execute(
                m, model='m', prompt='x', request_id='r', actor='agent')
    def test_failure_sanitized(self):
        def fail(_): raise RuntimeError('secret-value')
        with self.assertRaisesRegex(AvelloError, '^avello_transport_failure$'):
            AvelloExecutionAdapter(enabled=True, transport=fail).execute(
                running(), model='m', prompt='x', request_id='r', actor='agent')

if __name__ == '__main__': unittest.main()
