FROM php:alpine

ARG ALPINE_MIRROR

COPY src /webhook/
WORKDIR /webhook

RUN set -e; \
# Switch to a mirror if given
    if [ -n "$ALPINE_MIRROR" ]; then \
        sed -i 's!http://dl-cdn.alpinelinux.org!'"$ALPINE_MIRROR"'!g' /etc/apk/repositories; \
    fi; \
# Install build dependency packages
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; \
    pecl install swoole; \
    docker-php-ext-enable swoole; \
    apk del .build-deps; \
# Install run dependency packages
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
        | tr ',' '\n' \
        | sort -u \
        | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --virtual .php-rundeps $runDeps; \
# Cleanup
    rm -rf /tmp/pear; \
    rm -rf /var/cache/apk/*; \
# System configurations
    { \
        echo 'error_reporting = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
    } | tee /usr/local/etc/php/conf.d/docker.ini; \
    curl -s http://getcomposer.org/installer | php; \
    php composer.phar install --no-dev; \
    rm composer.phar;

CMD [ "php", "/webhook/console.php", "webhookd" ]