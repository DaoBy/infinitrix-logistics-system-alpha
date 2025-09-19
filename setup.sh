#!/bin/bash

set -e  # Exit if any command fails

echo -e "\n1. Cleaning cache..."
rm -rf vendor/
rm -f bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
echo "✅ Cache cleared"

echo -e "\n2. Installing backend..."
composer install --no-scripts --optimize-autoloader
echo "✅ Composer packages installed"

echo -e "\n3. Configuring environment..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
    echo "✅ .env created and app key generated"
else
    echo "✅ .env already exists"
fi

echo -e "\n4. Running migrations and seeding database..."
php artisan migrate:fresh --seed
echo "✅ Database migrated and seeded"

echo -e "\n5. Finalizing Laravel..."
php artisan optimize:clear
php artisan package:discover --ansi
echo "✅ Laravel optimized"

echo -e "\n6. Setting up frontend..."
if [ -f "package.json" ]; then
    npm install
    echo "✅ Node modules installed"
else
    echo "⚠️  No package.json found, skipping frontend setup"
fi

echo -e "\n🎉 Setup complete! Your Laravel + Inertia app is ready to launch!"
