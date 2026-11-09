ARG PHP_VERSION=8.3
ARG DEBIAN_VARIANT=trixie
FROM php:${PHP_VERSION}-apache-${DEBIAN_VARIANT}

ARG OPENCART_VERSION=3.0.5.0
# Empty: take the URL and the SHA-256 from opencart-releases.
ARG OPENCART_ZIP_URL=
ARG OPENCART_ZIP_SHA256=
ARG XDEBUG_VERSION=3.5.3

# PHP 8.5 has opcache built in, so only older versions install it as an extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev \
        unzip curl default-mysql-client rsync \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip \
    && if php -r 'exit(PHP_VERSION_ID < 80500 ? 0 : 1);'; then docker-php-ext-install opcache; fi \
    && a2enmod rewrite headers access_compat \
    && printf '[client]\nskip-ssl\n' > /etc/mysql/conf.d/zz-dev-no-ssl.cnf \
    && echo 'ServerName localhost' > /etc/apache2/conf-enabled/servername.conf \
    && rm -rf /var/lib/apt/lists/*

# Xdebug (enabled/disabled at runtime via XDEBUG_MODE)
RUN pecl install "xdebug-${XDEBUG_VERSION}" && docker-php-ext-enable xdebug

# Download OpenCart core into /usr/src/opencart (copied to the volume on first start)
COPY opencart-releases opencart-download /tmp/
RUN sh /tmp/opencart-download "${OPENCART_VERSION}" "${OPENCART_ZIP_URL}" "${OPENCART_ZIP_SHA256}" /tmp/opencart-releases \
    && mkdir -p /tmp/opencart \
    && unzip -q /tmp/opencart.zip -d /tmp/opencart \
    && UPLOAD_DIR="$(dirname "$(find /tmp/opencart -type f -name index.php -path '*upload/index.php' | head -n1)")" \
    && mv "${UPLOAD_DIR}" /usr/src/opencart \
    && rm -rf /tmp/opencart /tmp/opencart.zip /tmp/opencart-releases /tmp/opencart-download

COPY php.ini /usr/local/etc/php/conf.d/zz-opencart.ini
COPY apache-log.conf /etc/apache2/conf-enabled/zz-opencart-log.conf
COPY entrypoint.sh /usr/local/bin/opencart-entrypoint
COPY scripts/ /usr/local/bin/
RUN chmod +x /usr/local/bin/opencart-entrypoint /usr/local/bin/oc-*

WORKDIR /var/www/html

ENTRYPOINT ["opencart-entrypoint"]
CMD ["apache2-foreground"]
