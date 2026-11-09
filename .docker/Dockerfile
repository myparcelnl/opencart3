ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-apache

ARG OPENCART_VERSION=3.0.3.9
ARG OPENCART_ZIP_URL=https://github.com/opencart/opencart/releases/download/${OPENCART_VERSION}/opencart-${OPENCART_VERSION}.zip

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev \
        unzip curl default-mysql-client rsync \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip opcache \
    && a2enmod rewrite headers \
    && printf '[client]\nskip-ssl\n' > /etc/mysql/conf.d/zz-dev-no-ssl.cnf \
    && echo 'ServerName localhost' > /etc/apache2/conf-enabled/servername.conf \
    && rm -rf /var/lib/apt/lists/*

# Xdebug (enabled/disabled at runtime via XDEBUG_MODE)
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/conf.d/zz-opencart.ini

# Download OpenCart core into /usr/src/opencart (copied to the volume on first start)
RUN curl -fsSL -o /tmp/opencart.zip "${OPENCART_ZIP_URL}" \
    && mkdir -p /tmp/opencart \
    && unzip -q /tmp/opencart.zip -d /tmp/opencart \
    && UPLOAD_DIR="$(dirname "$(find /tmp/opencart -type f -name index.php -path '*upload/index.php' | head -n1)")" \
    && mv "${UPLOAD_DIR}" /usr/src/opencart \
    && rm -rf /tmp/opencart /tmp/opencart.zip

COPY entrypoint.sh /usr/local/bin/opencart-entrypoint
COPY scripts/ /usr/local/bin/
RUN chmod +x /usr/local/bin/opencart-entrypoint /usr/local/bin/oc-*

WORKDIR /var/www/html

ENTRYPOINT ["opencart-entrypoint"]
CMD ["apache2-foreground"]
