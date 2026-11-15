import io
import json
import unittest
from router import RoutingError, forward, route_for, upstream_url

ENV = {
    'INVISION_BASE_URL': 'https://invision.example/v1', 'INVISION_API_KEY': 'example-a',
    'ROTEIA_BASE_URL': 'https://roteia.example/v1', 'ROTEIA_API_KEY': 'example-b',
    'AVELLO_BASE_URL': 'https://avello.example/v1', 'AVELLO_API_KEY': 'example-c',
    'HUB_ENABLE_CALLS': '1',
    'HUB_ALLOWED_MODELS': 'invision::model-1,roteia::model-1,avello::model-1,invision::qwen/qwen3.8-max,roteia::test-model,avello::test-model,invision::m',
}
class FakeResponse(io.BytesIO):
    status = 200
    headers = {'Content-Type': 'application/json'}
class RouterTests(unittest.TestCase):
    def test_routes_all_three(self):
        for provider in ['invision', 'roteia', 'avello']:
            with self.subTest(provider=provider):
                self.assertEqual(route_for(provider + '::model-1', ENV).provider, provider)
    def test_missing_prefix_fails_closed(self):
        with self.assertRaises(RoutingError): route_for('model-1', ENV)
    def test_unknown_provider_fails_closed(self):
        with self.assertRaises(RoutingError): route_for('other::model-1', ENV)
    def test_missing_key_fails_closed(self):
        with self.assertRaises(RoutingError): route_for('roteia::model-1', {'HUB_ALLOWED_MODELS':'roteia::model-1'})
    def test_no_http_url(self):
        bad = dict(ENV, INVISION_BASE_URL='http://insecure.example/v1')
        with self.assertRaises(RoutingError): route_for('invision::m', bad)
    def test_not_allowed_fails_closed(self):
        with self.assertRaises(RoutingError): route_for('invision::other-model', ENV)
    def test_calls_disabled_by_default(self):
        off = dict(ENV, HUB_ENABLE_CALLS='0')
        with self.assertRaises(RoutingError): forward({'model':'invision::model-1'}, env=off)
    def test_upstream_url(self):
        self.assertEqual(upstream_url('https://x.example/v1'), 'https://x.example/v1/responses')
    def test_forward_rewrites_model_and_preserves_body(self):
        seen = []
        def fake_open(req, timeout):
            seen.append(req)
            return FakeResponse(b'{"ok":true}')
        route, response = forward({'model':'invision::qwen/qwen3.8-max','input':'abc','stream':True}, env=ENV, opener=fake_open)
        self.assertEqual(route.provider, 'invision')
        self.assertEqual(json.loads(seen[0].data), {'model':'qwen/qwen3.8-max','input':'abc','stream':True})
        self.assertEqual(seen[0].full_url, 'https://invision.example/v1/responses')
        self.assertEqual(seen[0].get_header('Authorization'), 'Bearer example-a')
        self.assertEqual(response.read(), b'{"ok":true}')
    def test_exact_routing_roteia(self):
        urls = []
        def fake_open(req, timeout):
            urls.append(req.full_url)
            return FakeResponse(b'{}')
        forward({'model':'roteia::test-model'}, env=ENV, opener=fake_open)
        self.assertEqual(urls, ['https://roteia.example/v1/responses'])
    def test_exact_routing_avello(self):
        urls = []
        def fake_open(req, timeout):
            urls.append(req.full_url)
            return FakeResponse(b'{}')
        forward({'model':'avello::test-model'}, env=ENV, opener=fake_open)
        self.assertEqual(urls, ['https://avello.example/v1/responses'])
if __name__ == '__main__':
    unittest.main()
