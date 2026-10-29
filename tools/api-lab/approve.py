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
    p.add_argument('--action',required=True,choices=['run_create','chat_test','catalog_read','artifact_write','run_evidence'])
    p.add_argument('--args-file',required=True)
    p.add_argument('--authorization-ref',required=True)
    a=p.parse_args()
    key=os.environ['LAB_APPROVAL_SECRET']
    if len(key)<32:raise ValueError('Approval secret must have at least 32 characters')
    args=json.loads(Path(a.args_file).read_text())
    if 'approval' in args:
        raise ValueError('Exact args required, excluding approval token')
    from lab import validate_arguments, amount
    validate_arguments(a.action, dict(args, approval=''))
    if a.action == 'run_create':
        if args.get('purpose') != 'test' or amount(args['budget_brl']) <= 0:
            raise ValueError('Positive test budget required')
    elif args.get('purpose') != 'exception':
        raise ValueError('Exact exception args required')
    instance = os.environ['LAB_INSTANCE_ID']
    if not instance.strip() or not a.authorization_ref.strip():
        raise ValueError('Instance and external authorization reference required')
    payload={'instance_id':instance,'action':a.action,'hash':hashlib.sha256(canonical(args).encode()).hexdigest(),
             'expires':time.time()+300,'nonce':secrets.token_hex(16),'authorization_ref':a.authorization_ref}
    body=canonical(payload).encode().hex()
    print(body+'.'+hmac.new(key.encode(),body.encode(),hashlib.sha256).hexdigest())
