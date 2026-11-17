import unittest
from mission import Mission, MissionError, State

class MissionTests(unittest.TestCase):
    def test_domains(self):
        with self.assertRaises(MissionError): Mission('x', 'ECO')
        with self.assertRaises(MissionError): Mission('x', 'DEV', 'tenant')
        self.assertEqual(Mission('x', 'ECO', 'tenant').domain, 'ECO')
    def test_transitions_and_idempotency(self):
        m = Mission('work', 'DEV')
        self.assertEqual(m.transition(State.ASSIGNED, request_id='1', actor='operator'), State.ASSIGNED)
        self.assertEqual(m.transition(State.ASSIGNED, request_id='1', actor='operator'), State.ASSIGNED)
        self.assertEqual(len(m.events), 1)
        with self.assertRaises(MissionError): m.transition(State.COMPLETED, request_id='2', actor='agent')
        with self.assertRaises(MissionError): m.transition(State.EXECUTING, request_id='1', actor='operator')
    def test_no_fake_approval(self):
        m = Mission('work', 'DEV')
        for i, s in enumerate((State.ASSIGNED, State.EXECUTING, State.TESTING, State.AWAITING)):
            m.transition(s, request_id=str(i), actor='agent')
        m.add_evidence('sha256:abc')
        m.verified_by = 'via'
        with self.assertRaises(MissionError):
            m.transition(State.APPROVED, request_id='approve', actor='operator', independent_verification=True)
        self.assertEqual(m.state, State.AWAITING)
    def test_rework(self):
        m = Mission('work', 'DEV')
        for i, s in enumerate((State.ASSIGNED, State.EXECUTING, State.TESTING, State.AWAITING, State.REWORK)):
            m.transition(s, request_id=str(i), actor='agent')
        self.assertEqual(m.state, State.REWORK)
        self.assertEqual(m.transition(State.ASSIGNED, request_id='retry', actor='operator'), State.ASSIGNED)

if __name__ == '__main__': unittest.main()
