<?php
// Load Composer autoloader FIRST
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
require_once __DIR__ . '/Config.php';


$cloudName = Config::get('CLOUDINARY_CLOUD_NAME');
$apiKey = Config::get('CLOUDINARY_API_KEY');
$apiSecret = Config::get('CLOUDINARY_API_SECRET');

Configuration::instance([
    'cloud' => [
        'cloud_name' => $cloudName,
        'api_key'    => $apiKey,
        'api_secret' => $apiSecret,
    ],
    'url' => [
        'secure' => true
    ]
]);