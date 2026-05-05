#!/bin/sh

echo "=== INIT SERIE-TV CONTAINER ==="

cd /var/www/html

# =========================
# 📦 COMPOSER (cartella php/)
# =========================
if [ -f "php/composer.json" ]; then
    echo "Composer install..."

    cd php
    composer install --no-interaction
    cd ..
fi

# =========================
# 📦 NODE + TYPESCRIPT (cartella js/)
# =========================
if [ -f "js/package.json" ]; then
    echo "NPM install..."

    cd js
    npm install

    # build TS → JS
    if [ -f "tsconfig.json" ]; then
        echo "TypeScript build..."
        npx tsc
    fi

    cd ..
fi

exec php-fpm