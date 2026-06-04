#!/bin/sh

set -e

# Run setup only if this is the app container (to avoid duplicate runs if app & worker start together)
if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    echo "Starting Laravel App Container initialization..."

    # Copy env if not exists
    if [ ! -f ".env" ]; then
        echo "Creating .env file..."
        cp .env.example .env
    fi

    # Install composer dependencies
    if [ ! -d "vendor" ]; then
        echo "Installing Composer dependencies..."
        composer install --no-interaction --optimize-autoloader
    fi

    # Install npm dependencies and build if not already done
    if [ ! -d "node_modules" ] || [ ! -d "public/build" ]; then
        echo "Installing NPM dependencies and building assets..."
        npm install
        npm run build
    fi

    # Generate key if empty
    if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=base64:$" .env || [ -z "$(grep APP_KEY .env | cut -d '=' -f2)" ]; then
        echo "Generating APP_KEY..."
        php artisan key:generate
    fi

    # Set appropriate permissions for Laravel
    echo "Setting storage and bootstrap/cache permissions..."
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

    # Wait for MySQL to be ready
    echo "Waiting for MySQL database connection..."
    DB_HOST_VAL=${DB_HOST:-mysql}
    DB_PORT_VAL=${DB_PORT:-3306}
    DB_USER_VAL=${DB_USERNAME:-root}
    DB_PASS_VAL=${DB_PASSWORD:-}

    until php -r "
        try {
            new PDO('mysql:host=${DB_HOST_VAL};port=${DB_PORT_VAL};dbname=mysql', '${DB_USER_VAL}', '${DB_PASS_VAL}');
            exit(0);
        } catch (Exception \$e) {
            fwrite(STDERR, 'Connection failed: ' . \$e->getMessage() . PHP_EOL);
            exit(1);
        }
    " 2>/tmp/db_error.log; do
        echo "MySQL is unavailable - sleeping. Error detail:"
        cat /tmp/db_error.log
        sleep 2
    done
    echo "MySQL is up!"

    # Run migrations
    echo "Running migrations..."
    php artisan migrate --force

    echo "Starting PHP-FPM..."
    exec php-fpm
elif [ "${CONTAINER_ROLE}" = "worker" ]; then
    echo "Starting Laravel Queue Worker..."
    # Wait for app container to finish migrations and compile assets
    sleep 5
    exec php artisan queue:listen --tries=1 --timeout=0
else
    echo "Unknown CONTAINER_ROLE: ${CONTAINER_ROLE}"
    exec "$@"
fi
