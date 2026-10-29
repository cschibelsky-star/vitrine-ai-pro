"""Reproducible local acceptance run. Not an OS/container security boundary.

Drops known credentials from this process; blocks non-loopback Python sockets.
Never provisions a host, provider account, proxy, or production integration.
"""
import ipaddress
import os
from pathlib import Path
import socket
import sys
import unittest
from unittest.mock import patch


def loopback(address):
    try:
        return ipaddress.ip_address(address).is_loopback
    except ValueError:
        return False


def main():
    os.chdir(Path(__file__).resolve().parent)
    for name in ('INVISION_API_KEY', 'ROTEIA_API_KEY', 'OPENROUTER_API_KEY',
                 'LAB_APPROVAL_SECRET', 'LAB_ACCESS_TOKEN', 'LAB_CONFIG', 'LAB_DATABASE', 'LAB_INSTANCE_ID'):
        os.environ.pop(name, None)
    connect = socket.socket.connect
    connect_ex = socket.socket.connect_ex
    getaddrinfo = socket.getaddrinfo

    def guarded_connect(sock, address):
        if not isinstance(address, tuple) or not loopback(address[0]):
            raise PermissionError('offline_hml_external_network_denied')
        return connect(sock, address)

    def guarded_connect_ex(sock, address):
        if not isinstance(address, tuple) or not loopback(address[0]):
            raise PermissionError('offline_hml_external_network_denied')
        return connect_ex(sock, address)

    def guarded_dns(host, *args, **kwargs):
        if not loopback(host):
            raise PermissionError('offline_hml_external_dns_denied')
        return getaddrinfo(host, *args, **kwargs)

    with patch.object(socket.socket, 'connect', guarded_connect), \
         patch.object(socket.socket, 'connect_ex', guarded_connect_ex), \
         patch.object(socket, 'getaddrinfo', guarded_dns):
        suite = unittest.defaultTestLoader.discover('tests')
        result = unittest.TextTestRunner(verbosity=2).run(suite)
    return 0 if result.wasSuccessful() else 1


if __name__ == '__main__':
    sys.exit(main())
