# KOLD production deploy notes

- Host: `72.62.255.53` (`srv1317719`)
- URL: https://kold.tedku.cloud
- Code path: `/var/www/kold` (do not touch other `/var/www/*`)
- Nginx site: `/etc/nginx/sites-available/kold.tedku.cloud`
- PHP: `unix:/run/php/php8.4-fpm.sock`
- DB: SQLite at `database/database.sqlite` (owned by `www-data`)
- TLS: Let's Encrypt `kold.tedku.cloud` only

## Reload after code update

```bash
cd /var/www/kold
# sync new code here
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database
systemctl reload nginx
```

## Isolation

Never edit `social-tedku`, `fatemap`, or other nginx sites when maintaining KOLD.
