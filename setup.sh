#!/bin/bash


echo -e "\n1. Cleaning cache..."
rm -rf vendor/ bootstrap/cache/* storage/framework/cache/* storage/framework/views/*
echo " Cache cleared"


echo -e "\n2. Installing backend..."
composer install --no-scripts --optimize-autoloader
echo " Composer packages installed"


echo -e "\n3. Configuring environment..."
[ ! -f ".env" ] && cp .env.example .env && php artisan key:generate
echo " Environment ready"


echo -e "\n5. Finalizing Laravel..."
php artisan optimize:clear
php artisan package:discover
echo " Laravel optimized"


echo -e "\n6. Setting up frontend..."
[ -f "package.json" ] && npm install 
echo " Frontend ready"

echo -e "\n Setup complete!"
