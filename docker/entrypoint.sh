#!/bin/sh
# Arranque: migraciones (dueñas del esquema, ADR-001) -> admin inicial (D-10) -> servidor.
set -e

# La configuración llega por variables de entorno; el .env real nunca entra a la imagen (Art. IX).
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
# El servidor embebido de PHP hereda el entorno (artisan serve lo filtra).
cd public
exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
