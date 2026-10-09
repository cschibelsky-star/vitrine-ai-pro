#!/usr/bin/env python3
"""Called before the first production mutation. Inputs cannot supply checks."""
import json
import os
import re
import sys
import urllib.request


def consume(project, sha, executor):
    endpoint = os.environ.get('PUBLICATION_CONTROL_URL', '')
    token = os.environ.get('PUBLICATION_EXECUTOR_TOKEN', '')
    if not endpoint.startswith('https://') or len(token) < 32 or not re.fullmatch('[a-f0-9]{40}', sha):
        raise RuntimeError('publication_control_not_configured')
    body = json.dumps({'project': project, 'sha': sha, 'executor': executor}).encode()
    request = urllib.request.Request(endpoint.rstrip('/') + '/api/publication/consume', data=body,
        headers={'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token})
    # Default SSL validation; urllib rejects TLS problems and non-2xx responses.
    with urllib.request.urlopen(request, timeout=30) as response:
        decision = json.load(response)
    if decision.get('allowed') is not True:
        raise RuntimeError('publication_blocked')


if __name__ == '__main__':
    try:
        consume(*sys.argv[1:])
    except Exception:
        print('PUBLICATION_BLOCKED: approval or trusted evidence unavailable', file=sys.stderr)
        sys.exit(1)
