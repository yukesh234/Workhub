<?php
use Cloudinary\Configuration\Configuration;
require_once __DIR__ . '/Config.php';

Configuration::instance([
'cloud' =>[
    'cloud_name' => Config::get('CLOUDINARY_CLOUD_NAME'),
    'api_key' => Config::get('CLOUDINARY_API_KEY'),
    'api_secret' => Config::get('CLOUDINARY_API_SECRET'),
    ],
    'url' => [
        'secure' => true
    ]
]);