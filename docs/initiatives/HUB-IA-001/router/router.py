"""Isolated Responses API router. Not a trusted production gateway."""
import json
import os
import re
import hmac
import urllib.error
import urllib.request
from dataclasses import dataclass
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

@dataclass(frozen=True)
class Route:
    provider: str
    upstream_model: str
    base_url: str
    key: str

class RoutingError(ValueError):
    pass

PROVIDERS = {
    'invision': ('INVISION_BASE_URL', 'INVISION_API_KEY'),
    'roteia': ('ROTEIA_BASE_URL', 'ROTEIA_API_KEY'),
    'avello': ('AVELLO_BASE_URL', 'AVELLO_API_KEY'),
}

def route_for(model_id: str, env=None) -> Route:
    env = os.environ if env is None else env
    prefix, sep, upstream = model_id.partition('::')
    if not sep or prefix not in PROVIDERS:
        raise RoutingError('unknown_or_unqualified_model')
    if not re.fullmatch(r'[a-zA-Z0-9._/-]{1,128}', upstream):
        raise RoutingError('invalid_upstream_model')
    allowed = {x.strip() for x in env.get('HUB_ALLOWED_MODELS', '').split(',') if x.strip()}
    if model_id not in allowed:
        raise RoutingError('model_not_allowed')
    base_var, key_var = PROVIDERS[prefix]
    base = env.get(base_var, '').rstrip('/')
    key = env.get(key_var, '')
    if not base.startswith('https://') or not key:
        raise RoutingError('provider_not_configured')
    if '?' in base or '#' in base or '@' in base:
        raise RoutingError('invalid_provider_base_url')
    return Route(prefix, upstream, base, key)

def upstream_url(base: str) -> str:
    return base + ('/responses' if base.endswith('/v1') else '/v1/responses')

def forward(payload: dict, *, env=None, opener=None):
    env = os.environ if env is None else env
    if env.get('HUB_ENABLE_CALLS') != '1':
        raise RoutingError('calls_disabled')
    if not isinstance(payload, dict) or not isinstance(payload.get('model'), str):
        raise RoutingError('model_required')
    route = route_for(payload['model'], env)
    outgoing = dict(payload)
    outgoing['model'] = route.upstream_model
    req = urllib.request.Request(upstream_url(route.base_url),
        data=json.dumps(outgoing).encode('utf-8'), method='POST',
        headers={'Authorization': 'Bearer ' + route.key,
                 'Content-Type': 'application/json',
                 'Accept': 'text/event-stream' if payload.get('stream') else 'application/json'})
    return route, (opener or urllib.request.urlopen)(req, timeout=75)

class Handler(BaseHTTPRequestHandler):
    protocol_version = 'HTTP/1.1'
    def send_bytes(self, code, body):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)
    def do_POST(self):
        token = os.environ.get('HUB_LOCAL_TOKEN', '')
        if not token or not hmac.compare_digest(self.headers.get('Authorization', ''), 'Bearer ' + token):
            return self.send_bytes(401, b'{"error":"unauthorized"}')
        if self.path != '/v1/responses':
            return self.send_bytes(404, b'{"error":"not_found"}')
        try:
            size = int(self.headers.get('Content-Length', 0))
            if not 0 < size <= 2_000_000:
                return self.send_bytes(413, b'{"error":"invalid_body_size"}')
            payload = json.loads(self.rfile.read(size))
            _, resp = forward(payload)
            with resp:
                self.send_response(resp.status)
                self.send_header('Content-Type', resp.headers.get('Content-Type', 'application/json'))
                self.send_header('Connection', 'close')
                self.end_headers()
                while chunk := resp.read(16384):
                    self.wfile.write(chunk)
                    self.wfile.flush()
                self.close_connection = True
        except (RoutingError, ValueError, TypeError) as exc:
            self.send_bytes(422, json.dumps({'error': str(exc)}).encode())
        except urllib.error.HTTPError as exc:
            self.send_bytes(502, json.dumps({'error': 'upstream_http_error', 'status': exc.code}).encode())
        except (urllib.error.URLError, TimeoutError, OSError):
            self.send_bytes(502, b'{"error":"upstream_unavailable"}')
    def log_message(self, format, *args):
        pass

def main():
    port = int(os.environ.get('HUB_LOCAL_PORT', '18761'))
    if not 1024 <= port <= 65535:
        raise ValueError('invalid port')
    server = ThreadingHTTPServer(('127.0.0.1', port), Handler)
    print('HUB-IA-001 router listening on 127.0.0.1:' + str(port), flush=True)
    server.serve_forever()

if __name__ == '__main__':
    main()
