## dogecloud s3 for laravel

### step one: install this package
```angular2html
composer require ufree/laravel-doge-cloud
```
### step two: set .env file

```
DOGE_ACCESS_KEY_ID
DOGE_SECRET_ACCESS_KEY
DOGE_BUCKET
DOGE_URL
DOGE_ENDPOINT
DOGE_USE_PATH_STYLE_ENDPOINT
```

### step three: execute env config
```
php artisan vendor:publish --tag=dogecloud-config
```