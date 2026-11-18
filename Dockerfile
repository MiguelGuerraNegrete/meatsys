# Imagen de MeatSys para Render (plan gratuito) u otro servicio que ejecute Docker.
FROM php:8.3-apache

# Extensiones de PHP que usa MeatSys
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype-dev libzip-dev libicu-dev libsqlite3-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd zip intl pdo_mysql bcmath \
 && a2enmod rewrite headers \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Dependencias de producción (si la carpeta vendor no viene incluida)
RUN if [ ! -f vendor/autoload.php ]; then COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress; else COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev; fi

# Base de datos de demostración creada durante la construcción, para que el
# servicio arranque rápido. Se copia a su lugar en cada arranque.
ENV APP_ENV=production DB_CONNECTION=sqlite DB_DATABASE=/var/www/html/database/database.sqlite \
    SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync LOG_CHANNEL=stderr MAIL_MAILER=log
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache public/uploads \
 && rm -f database/database.sqlite && touch database/database.sqlite \
 && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" php artisan migrate --force \
 && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" php artisan db:seed --class=DemoSeeder --force \
 && cp database/database.sqlite database/demo.sqlite \
 && tar -C storage/app -cf database/demo-storage.tar private \
 && rm -f storage/logs/*.log

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/meatsys-entrypoint
RUN chmod +x /usr/local/bin/meatsys-entrypoint \
 && chown -R www-data:www-data storage bootstrap/cache database public/uploads

ENV PORT=10000
EXPOSE 10000
CMD ["meatsys-entrypoint"]
