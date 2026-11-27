# PHP 8.4 FPM para a API Laravel (somente desenvolvimento)
FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
        libwebp-dev libfreetype6-dev libmagickwand-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl gd exif zip bcmath opcache pcntl \
    # Imagick: lê TIFF, preserva o perfil de cor e gera WebP/JPEG das obras
    && pecl install imagick && docker-php-ext-enable imagick \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-taigo.ini

# Usuário com o mesmo UID/GID do WSL, para os arquivos criados não ficarem como root
RUN groupadd -g "${GID}" app && useradd -m -u "${UID}" -g app app \
    && sed -i 's/^user = .*/user = app/; s/^group = .*/group = app/' /usr/local/etc/php-fpm.d/www.conf

USER app
WORKDIR /var/www/backend
