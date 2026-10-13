#!/bin/sh
# Startup: migrations (owners of the schema, ADR-001) -> initial admin (D-10) -> server.
set -e

# Configuration comes from environment variables; the real .env never enters the image (Art. IX).
[ -f .env ] || touch .env

if [ "$1" = "test" ]; then
  shift
  exec php artisan test "$@"
fi

until php artisan migrate --force; do
  echo "Esperando la base de datos..."
  sleep 2
done
php artisan library:bootstrap-admin
# PHP's built-in server inherits the environment (artisan serve filters it).
cd public
exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
