<?php
return [
    'access_key' => env('DOGE_ACCESS_KEY_ID', ''),
    'secret_key' => env('DOGE_SECRET_ACCESS_KEY', ''),
    'region' => 'automatic',
    'bucket' => env('DOGE_BUCKET', ''),
    'url' => env('DOGE_URL', ''),
    'endpoint' => env('DOGE_ENDPOINT', ''),
    'use_path_style_endpoint' => env('DOGE_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
];
