FROM php:8.5-fpm-alpine

# Установка легковесных системных библиотек (libpq-dev вместо тяжелого postgresql-dev с LLVM)
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    bash \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j2 \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Установка Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Установка PHP-зависимостей с кэшированием слоев
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Копирование файлов проекта
COPY . /var/www/html

# Установка прав доступа для storage и bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
