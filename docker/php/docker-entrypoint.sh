set -e

# Wait for the database to be ready
/usr/local/bin/wait-for-it database:3306 -t 60

# Run migrations
php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction

# Start PHP-FPM
php-fpm

# Execute the passed command
exec "$@"