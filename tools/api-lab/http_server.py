"""Loopback-only MCP Streamable HTTP pilot behind a TLS reverse proxy.

Single trusted operator. No public listener, OAuth, or multi-tenant semantics.
"""
import hmac
import json
import os
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from lab import Lab, canonical, rpc

class LabHTTPServer(ThreadingHTTPServer):
    daemon_threads = True
    def __init__(self, address, lab, token, hosts, origins):
        if address[0] != '127.0.0.1':
            raise ValueError('loopback_bind_required')
        if len(token) < 32 or not hosts:
            raise ValueError('strong_token_and_hosts_required')
        self.lab, self.token, self.hosts, self.origins = lab, token, set(hosts), set(origins)
        self.lock = threading.Lock()
        self.slots = threading.BoundedSemaphore(8)
        super().__init__(address, Handler)

    def process_request(self, request, client_address):
        if not self.slots.acquire(blocking=False):
            self.shutdown_request(request)
            return
        try:
            super().process_request(request, client_address)
        except Exception:
            self.slots.release()
            raise

    def process_request_thread(self, request, client_address):
        try:
            super().process_request_thread(request, client_address)
        finally:
            self.slots.release()

class Handler(BaseHTTPRequestHandler):
    protocol_version = 'HTTP/1.1'
    def log_message(self, *args):
        pass  # Do not log credentials, prompts, tokens, paths or response bodies.

    def send(self, status, body=None):
        data = b'' if body is None else canonical(body).encode()
        self.send_response(status)
        self.send_header('Content-Length', str(len(data)))
        self.send_header('Content-Type', 'application/json')
        self.send_header('Cache-Control', 'no-store')
        self.send_header('Connection', 'close')
        self.end_headers()
        self.wfile.write(data)
        self.close_connection = True

    def check(self):
        self.connection.settimeout(10)
        if self.path != '/mcp':
            self.send(404); return False
        if self.headers.get('Host') not in self.server.hosts:
            self.send(403); return False
        origin = self.headers.get('Origin')
        if origin is not None and origin not in self.server.origins:
            self.send(403); return False
        auth = self.headers.get('Authorization', '')
        if not hmac.compare_digest(auth, 'Bearer ' + self.server.token):
            self.send(401); return False
        return True

    def do_GET(self):
        if self.check():
            self.send(405)  # This stateless server has no standalone SSE stream.

    def do_DELETE(self):
        if self.check():
            self.send(405)

    def do_POST(self):
        if not self.check():
            return
        if self.headers.get('Transfer-Encoding'):
            self.send(400); return
        if self.headers.get('Content-Type', '').split(';')[0].strip() != 'application/json':
            self.send(415); return
        accept = self.headers.get('Accept', '')
        if 'application/json' not in accept or 'text/event-stream' not in accept:
            self.send(406); return
        try:
            length = int(self.headers.get('Content-Length', '0'))
            if not 0 < length <= 100000:
                self.send(413); return
            data = self.rfile.read(length)
            if len(data) != length:
                self.send(400); return
            msg = json.loads(data)
            if (not isinstance(msg, dict) or msg.get('jsonrpc') != '2.0'
                or not isinstance(msg.get('method'), str)
                or not isinstance(msg.get('params', {}), dict)):
                self.send(400); return
            if msg['method'] != 'initialize' and self.headers.get('MCP-Protocol-Version') not in ('2024-11-05','2025-03-26','2025-06-18'):
                self.send(400); return
            # One operation at a time: fail fast instead of creating an unbounded paid queue.
            if not self.server.lock.acquire(blocking=False):
                self.send(429); return
            try:
                response = rpc(self.server.lab, msg)
            finally:
                self.server.lock.release()
            self.send(202 if response is None else 200, response)
        except Exception:
            self.send(400)

if __name__ == '__main__':
    os.umask(0o077)
    from pathlib import Path
    config = json.loads(Path(os.environ['LAB_CONFIG']).read_text())
    # HTTP dispatch runs in a worker thread; lock serializes access to this connection.
    lab = Lab(os.environ['LAB_DATABASE'], config, threaded=True)
    server = LabHTTPServer(('127.0.0.1', int(os.environ.get('LAB_PORT','8898'))), lab,
        os.environ['LAB_ACCESS_TOKEN'], os.environ['LAB_ALLOWED_HOSTS'].split(','),
        os.environ.get('LAB_ALLOWED_ORIGINS','').split(','))
    server.serve_forever()
