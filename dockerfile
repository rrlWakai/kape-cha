FROM dunglas/frankenphp:php8.3

WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    xml \
    curl \
    zip \
    bcmath \
    intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN php artisan optimize:clear

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 8000

CMD ["frankenphp", "php-server", "--listen", ":8000", "--root", "public"]