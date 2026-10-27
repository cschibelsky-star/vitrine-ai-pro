"""Offline operator tool. Do not expose to the assistant as MCP or mount its key there."""
import argparse
import hashlib
import hmac
import json
import os
import secrets
import time
from pathlib import Path
from lab import canonical

if __name__ == '__main__':
    p=argparse.ArgumentParser()
    p.add_argument('--action',required=True,choices=['chat_test','catalog_read','artifact_write','run_evidence'])
    p.add_argument('--args-file',required=True)
    p.add_argument('--authorization-ref',required=True)
    a=p.parse_args()
    key=os.environ['LAB_APPROVAL_SECRET']
    if len(key)<32:raise ValueError('Approval secret must have at least 32 characters')
    args=json.loads(Path(a.args_file).read_text())
    if args.get('purpose')!='exception' or 'approval' in args:raise ValueError('Exact exception args required, excluding approval token')
    payload={'action':a.action,'hash':hashlib.sha256(canonical(args).encode()).hexdigest(),
             'expires':time.time()+300,'nonce':secrets.token_hex(16),'authorization_ref':a.authorization_ref}
    body=canonical(payload).encode().hex()
    print(body+'.'+hmac.new(key.encode(),body.encode(),hashlib.sha256).hexdigest())
