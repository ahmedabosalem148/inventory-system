#!/bin/sh
set -e

echo "🚀 Starting Inventory System..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL..."
until php artisan db:show 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "✅ MySQL is ready!"

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Seed database if needed
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Clear and cache config
echo "⚙️ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if not exists
if [ ! -L "/var/www/html/public/storage" ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start Laravel server
echo "🎉 Starting Laravel server on port 8000..."
php artisan serve --host=0.0.0.0 --port=8000
