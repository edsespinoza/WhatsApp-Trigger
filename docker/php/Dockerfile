FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git curl zip unzip bash \
    libpng-dev libxml2-dev oniguruma-dev

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar apenas os arquivos do Composer primeiro — aproveita cache de layer:
# se composer.json/lock não mudarem, esta etapa não roda de novo.
COPY composer.json composer.lock ./

# Instalar vendor DENTRO do container (filesystem Linux nativo, sem bind mount Windows).
# Isso é o que elimina a lentidão: vendor com 15k+ arquivos fica em volume Linux.
RUN composer install --no-interaction --no-scripts --optimize-autoloader

# Copiar configs PHP otimizadas para a imagem (não dependem de volume externo)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf    /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
