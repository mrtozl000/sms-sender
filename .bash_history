docker-compose down
exit
php artisan app:key generate
php artisan app:key 
php artisan key:generate
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
composer require darkaonline/l5-swagger
composer require darkaonline/l5-swagger:^8 --no-dev
composer update
exit
