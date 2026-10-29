import http.client
import json
import tempfile
import threading
import unittest
from lab import Lab
from http_server import LabHTTPServer

class HTTPTests(unittest.TestCase):
    def setUp(self):
        self.tmp=tempfile.TemporaryDirectory()
        self.lab=Lab(self.tmp.name+'/db',{'max_run_brl':'1','max_total_brl':'1','providers':{}},threaded=True)
        self.server=LabHTTPServer(('127.0.0.1',0),self.lab,'x'*32,['lab.example'],['https://client.example'])
        self.worker=threading.Thread(target=self.server.serve_forever,daemon=True);self.worker.start()
    def tearDown(self):
        self.server.shutdown();self.server.server_close();self.worker.join();self.lab.db.close();self.tmp.cleanup()
    def req(self,msg=None,method='POST',**headers):
        conn=http.client.HTTPConnection('127.0.0.1',self.server.server_port,timeout=3)
        hs={'Host':'lab.example','Authorization':'Bearer '+'x'*32,'Content-Type':'application/json',
            'Accept':'application/json, text/event-stream','MCP-Protocol-Version':'2025-06-18'}
        hs.update(headers)
        conn.request(method,'/mcp',body=json.dumps(msg or {'jsonrpc':'2.0','id':1,'method':'ping'}),headers=hs)
        r=conn.getresponse();data=r.read();conn.close();return r.status,data
    def test_ping(self):
        s,b=self.req();self.assertEqual(s,200);self.assertEqual(json.loads(b)['result'],{})
    def test_auth_required(self):self.assertEqual(self.req(Authorization='')[0],401)
    def test_origin_blocked(self):self.assertEqual(self.req(Origin='https://evil.example')[0],403)
    def test_host_blocked(self):self.assertEqual(self.req(Host='evil.example')[0],403)
    def test_get_no_sse(self):self.assertEqual(self.req(method='GET')[0],405)
    def test_protocol_required(self):self.assertEqual(self.req(**{'MCP-Protocol-Version':''})[0],400)
    def test_notification_accepted(self):
        s,b=self.req({'jsonrpc':'2.0','method':'notifications/initialized'})
        self.assertEqual((s,b),(202,b''))
    def test_rejects_incomplete_accept(self):self.assertEqual(self.req(Accept='application/json')[0],406)
    def test_lock_blocks_parallel(self):
        self.server.lock.acquire()
        try:self.assertEqual(self.req()[0],429)
        finally:self.server.lock.release()
    def test_tools_status(self):
        s,b=self.req({'jsonrpc':'2.0','id':2,'method':'tools/call','params':{'name':'lab_status','arguments':{}}})
        self.assertEqual(s,200);self.assertEqual(json.loads(json.loads(b)['result']['content'][0]['text'])['mode'],'test_only')
    def test_no_public_bind(self):
        with self.assertRaises(ValueError):LabHTTPServer(('0.0.0.0',0),self.lab,'x'*32,['lab.example'],[])

if __name__=='__main__':unittest.main()
