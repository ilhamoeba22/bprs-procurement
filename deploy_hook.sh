#!/bin/bash

echo "===== Starting Deployment ====="

TARGET=/home/bprw7255/bprs_procurement
GIT_DIR=/home/bprw7255/repos/bprs-procurement.git

# Checkout latest code
echo "Checking out latest code..."
git --work-tree=$TARGET --git-dir=$GIT_DIR checkout -f main

cd $TARGET

# Install/update composer dependencies
echo "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Migrate database (tanpa fresh!)
echo "Running migrations..."
php artisan migrate --force

# Clear cache Laravel
echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize
echo "Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "===== Deployment Complete ====="
