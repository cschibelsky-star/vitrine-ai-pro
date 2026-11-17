#!/bin/sh
set -eu
umask 077
# Generate once inside a dedicated volume. Never echo key or reuse Core/PROD secrets.
if [ ! -s /var/lib/assessor/app.key ]; then
    php -r 'file_put_contents("/var/lib/assessor/app.key", "base64:".base64_encode(random_bytes(32)));'
fi
APP_KEY="$(cat /var/lib/assessor/app.key)"
export APP_KEY
[ -f /var/lib/assessor/database.sqlite ] || touch /var/lib/assessor/database.sqlite
# No migration, seed, account creation or trial activation at startup.
exec "$@"
