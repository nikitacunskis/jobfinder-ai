#!/bin/sh
set -e

php artisan config:clear

MIGRATION_COUNT=$(php artisan tinker --execute='echo Schema::hasTable("migrations") ? DB::table("migrations")->count() : 0;' 2>/dev/null || echo 0)

if [ "$MIGRATION_COUNT" = "0" ]; then
    php artisan migrate:fresh --force
else
    php artisan migrate --force
fi

php artisan jobsucher:import-profile public/profile.json
php artisan jobsucher:import-jobs public/jobs.json
php artisan jobsucher:reindex || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8765}"
