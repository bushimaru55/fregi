FROM php:8.1-fpm

# 依存
RUN apt-get update && apt-get install -y   git unzip libzip-dev libonig-dev libicu-dev   && docker-php-ext-install pdo_mysql mbstring intl zip   && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ
WORKDIR /var/www

# php.ini（任意）
COPY php.ini /usr/local/etc/php/conf.d/zz-local.ini

