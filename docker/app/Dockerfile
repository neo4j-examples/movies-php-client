FROM php:8.4-cli-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache unzip

WORKDIR /opt/project

COPY public/ composer.json composer.lock index.php /opt/project/

COPY docker/app/entrypoint.sh /opt/entrypoint.sh

ENTRYPOINT [ "/opt/entrypoint.sh" ]

RUN composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --optimize-autoloader \
  --classmap-authoritative \
  --no-scripts \
  --prefer-dist && \
  composer check-platform-reqs --no-dev && \
  chmod +x /opt/entrypoint.sh

CMD composer run start
