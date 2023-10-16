<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## Installation

<p>composer update - To update all the packages of composer</p> 
<p>Doctrine/dbal; Fortify and JetStream need to installed apart from by default compose.json</p> 
<p>php artisan key:generate - To generate the base64 key for initialising the project - if not existing</p>
<p>php artisan migrate</p>
<p>php artisan passport:install  --> Oauth Implementation</p> 

## Authentication for API - passport

https://laravel.com/docs/8.x/passport

## General Laravel commands

<u>CLEAR CACHES</u><br>
php artisan cache:clear<br>
php artisan config:clear<br>
php artisan route:clear<br>
php artisan view:clear<br>

<u>MODEL</u><br>
php artisan make:model ModelName<br>

<u>CONTROLLER</u><br>
php artisan make:controller /Api/v1/PostsController --resource<br>

<u>CREATE / MIGRATION</u><br>
php artisan make:migration create_salutations_table<br>

<u>RUN / MIGRATION</u><br>
php artisan migrate<br>
php artisan migrate --force<br>
php artisan migrate:refresh<br>
php artisan migrate:refresh --seed<br>
php artisan migrate:reset<br>
php artisan migrate:rollback --step 3<br>
php artisan migrate --path=/database/migrations/<br>

<u>CREATE / SEEDERS</u><br>
php artisan make:seeder NameSeeder<br>

<u>RUN / SEEDERS</u><br>
php artisan db:seed<br>
php artisan db:seed --class=UserSeeder<br>

<u>CLEAR ALL CACHES</u><br>
php artisan optimize<br>

<u>Laravel ohne erzeugtes Verzeichnis installieren</u><br>
composer create-project laravel/laravel . --prefer-dist<br>

<u>Laravel mit Verzeichnis installieren</u><br>
composer create-project laravel/laravel example-app<br>

## FORGE deployment settings
cd /home/forge/backend.omnics.in<br>
git pull origin $FORGE_SITE_BRANCH<br>
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader<br><br>

( flock -w 10 9 || exit 1<br>
echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock<br>

if [ -f artisan ]; then<br>
$FORGE_PHP artisan migrate<br>
$FORGE_PHP artisan storage:link<br>
$FORGE_PHP artisan config:clear<br>
$FORGE_PHP artisan cache:clear<br>
fi<br>
