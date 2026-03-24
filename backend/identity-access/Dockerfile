FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .

RUN composer dump-autoload --optimize && php bin/console cache:clear --no-warmup

EXPOSE 8081

CMD ["sh", "-c", "php bin/console app:iam:init-schema && php -S 0.0.0.0:8081 -t public"]
