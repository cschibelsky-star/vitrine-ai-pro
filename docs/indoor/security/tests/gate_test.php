<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/IndoorRemoteMediaGate.php';
function blocked(callable $f, string $name): void {
    try { $f(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException('Expected rejection: ' . $name);
}
$allow = ['media.example.com'];
if (IndoorRemoteMediaGate::validateUrl('https://media.example.com/a.jpg', $allow) !== 'media.example.com') {
    throw new RuntimeException('Valid host failed');
}
foreach ([
    'http://media.example.com/a', 'file:///etc/passwd', 'https://127.0.0.1/a',
    'https://169.254.169.254/latest/meta-data', 'https://media.example.com@127.0.0.1/a',
    'https://media.example.com:8443/a', 'https://evil.example.com/a',
    'https://media.example.com./a#fragment', 'https://media.example.com/a#fragment'
] as $url) {
    blocked(static fn() => IndoorRemoteMediaGate::validateUrl($url, $allow), $url);
}
foreach (['127.0.0.1', '10.1.2.3', '192.168.1.1', '169.254.169.254', '::1', 'fc00::1', 'fe80::1', '0.0.0.0'] as $ip) {
    blocked(static fn() => IndoorRemoteMediaGate::validateResolvedAddresses([$ip]), $ip);
}
blocked(static fn() => IndoorRemoteMediaGate::validateResolvedAddresses(['8.8.8.8', '10.0.0.1']), 'mixed DNS');
blocked(static fn() => IndoorRemoteMediaGate::validateResolvedAddresses([]), 'empty DNS');
blocked(static fn() => IndoorRemoteMediaGate::redirectTarget('https://internal.example/a', $allow), 'redirect');
IndoorRemoteMediaGate::validateResolvedAddresses(['8.8.8.8', '2606:4700:4700::1111']);
echo "OK: Indoor remote media preflight policy (no network)\n";
