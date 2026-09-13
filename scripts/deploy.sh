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

BACKUP_STAMP="$(date +%Y%m%d-%H%M%S)"
"${SSH[@]}" "$USER@$HOST" "set -e; BACKUP_DIR=/var/backups/kold/$BACKUP_STAMP; mkdir -p \"\$BACKUP_DIR\"; cp /var/www/kold/database/database.sqlite \"\$BACKUP_DIR/database.sqlite\"; cp /var/www/kold/.env \"\$BACKUP_DIR/.env\"; tar --exclude=vendor --exclude=node_modules --exclude=storage/logs --exclude=storage/framework --exclude=database/database.sqlite -czf \"\$BACKUP_DIR/app.tar.gz\" -C /var/www/kold .; cd /var/www/kold; php artisan down --retry=60"
echo "Backup created at /var/backups/kold/$BACKUP_STAMP"

rsync -az --delete \
  --exclude='.env' \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='database/database.sqlite' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/down' \
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
php artisan config:cache
php artisan route:cache
php artisan view:cache
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database
php artisan up
echo DEPLOY_OK
EOF

echo "Live: https://kold.tedku.cloud"
