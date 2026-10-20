#!/bin/sh
set -e

cat > .env <<ENVEOF
APP_NAME="UPT Lab Terpadu"
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_KEY=${APP_KEY:-}
APP_URL=${APP_URL:-http://localhost}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${LOG_LEVEL:-warning}

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-db_lab_terpadu}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-secret}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=memcached

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@lab-terpadu.local"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"

FRONTEND_URL=${FRONTEND_URL:-http://localhost}

# Render PDF dari .docx (docx-preview + Chromium headless)
PDF_DOCX_ENABLED=${PDF_DOCX_ENABLED:-true}
PDF_USE_WORD_COM=${PDF_USE_WORD_COM:-false}
PDF_CHROME_PATH=${PDF_CHROME_PATH:-/usr/bin/chromium-browser}
ENVEOF

if [ -z "${APP_KEY}" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
fi

echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
until php -r "
try {
    new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-db_lab_terpadu}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-secret}');
    echo 'connected';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    printf '.'
    sleep 2
done
echo ""
echo "MySQL is ready!"

php artisan config:cache
php artisan view:cache
php artisan storage:link --force
php artisan migrate --force
# Seeder idempotent (roles, permission, akun demo, harga instrumen, form template)
php artisan db:seed --force

exec "$@"