# KOLD production deploy

Stable URL: **https://kold.tedku.cloud**

This document is the source of truth so any human or Cursor agent can deploy without guessing.

## Server facts

| Item | Value |
|------|--------|
| Host | `72.62.255.53` (`srv1317719`) |
| SSH | `root@72.62.255.53` (or Hostinger alias `tedkucloud` on Karl's Mac) |
| App path | `/var/www/kold` |
| Nginx site | `/etc/nginx/sites-available/kold.tedku.cloud` |
| PHP-FPM | `unix:/run/php/php8.4-fpm.sock` |
| Database | SQLite `database/database.sqlite` (`www-data`) |
| TLS | Let's Encrypt for `kold.tedku.cloud` only |

## Isolation (mandatory)

Only touch KOLD paths above.

**Do not** edit or restart other apps under `/var/www/*` (for example `social-tedku`, `fatemap`, `chatinsights`, …) or other files in `/etc/nginx/sites-enabled/` except `kold.tedku.cloud`.

## Preferred: CI-gated automatic deploy

A Pull Request must first pass [`.github/workflows/ci.yml`](.github/workflows/ci.yml). After it is merged, the successful **CI** run on `main` triggers [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml).

The deploy workflow does not cancel an in-progress production deployment. This prevents a newer commit from interrupting an older release after maintenance mode has started.

Required GitHub Actions secrets (repo → Settings → Secrets):

- `KOLD_DEPLOY_HOST` = `72.62.255.53`
- `KOLD_DEPLOY_SSH_KEY` = private key for the GitHub Actions deploy user (comment `github-actions-kold-deploy`)

You can also run the workflow manually: Actions → **Deploy KOLD** → Run workflow.

## Manual deploy (Mac / agent with SSH)

From a machine that can SSH to the VPS:

```bash
./scripts/deploy.sh
```

Or step by step:

```bash
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
  ./ root@72.62.255.53:/var/www/kold/

ssh root@72.62.255.53 'bash -s' <<'EOF'
set -euo pipefail
cd /var/www/kold
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database
EOF
```

Production deploys must not run `db:seed`. Demo data is for local development only. `scripts/deploy.sh` creates timestamped backups under `/var/backups/kold/` before enabling maintenance mode.

Preserve `/var/www/kold/.env` on the server. Never rsync a local `.env` over it.

## After OAuth / AI keys change

Edit only `/var/www/kold/.env` on the server, then:

```bash
ssh root@72.62.255.53 'cd /var/www/kold && php artisan config:cache'
```

## Why some Cursor agents say “no SSH”

Cloud Agent VMs do not include your Mac keys (`tedkucloud`, etc.). Each new cloud session is empty unless you add a key for that session.

Use **GitHub Actions** or a machine that already has SSH (this Mac) for deploys. Do not mint a new Hostinger key for every chat.
