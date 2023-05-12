FROM composer:2.5

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
  chmod +x /opt/entrypoint.sh

CMD [ "composer", "run", "start" ]