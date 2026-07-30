#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${KOLD_DEPLOY_HOST:-72.62.255.53}"
USER="${KOLD_DEPLOY_USER:-root}"
SSH_KEY="${KOLD_DEPLOY_SSH_KEY_FILE:-}"

SSH=(ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new)
RSYNC_SSH="ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new"
if [[ -n "$SSH_KEY" ]]; then
  SSH+=(-i "$SSH_KEY" -o IdentitiesOnly=yes)
  RSYNC_SSH="ssh -i $SSH_KEY -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
fi

echo "Deploying $ROOT -> $USER@$HOST:/var/www/kold"

rsync -az --delete \
  --exclude='.env' \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='database/database.sqlite' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='IGFB-Test.html' \
  --exclude='test.php' \
  --exclude='.DS_Store' \
  -e "$RSYNC_SSH" \
  "$ROOT/" "$USER@$HOST:/var/www/kold/"

"${SSH[@]}" "$USER@$HOST" 'bash -s' <<'EOF'
set -euo pipefail
cd /var/www/kold
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database
systemctl reload nginx
echo DEPLOY_OK
EOF

echo "Live: https://kold.tedku.cloud"
