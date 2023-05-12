#!/bin/sh

set -e

if [ ! -d vendor ]; then
  composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-scripts \
    --prefer-dist
fi

exec "$@"
