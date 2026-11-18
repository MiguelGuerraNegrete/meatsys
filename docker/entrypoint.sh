#!/bin/sh
# Arranque de MeatSys en el contenedor.
set -e
cd /var/www/html
: "${PORT:=10000}"
export PORT

# Apache escucha en el puerto que asigna la plataforma
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf

# Clave de la aplicación: si la plataforma no la define, se genera una en cada arranque
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
fi

# Base de datos: en el plan gratuito el disco no es persistente, así que en cada
# arranque se parte de los datos de demostración (o de una base vacía).
if [ "${MEATSYS_DEMO:-true}" = "true" ]; then
    cp database/demo.sqlite database/database.sqlite
    mkdir -p storage/app && tar -C storage/app -xf database/demo-storage.tar
else
    touch database/database.sqlite
fi
php artisan migrate --force
php artisan db:seed --force          # roles y administrador (no duplica)
php artisan meatsys:alertas || true   # alertas de vencimiento con la fecha de hoy

php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database public/uploads

exec apache2-foreground
