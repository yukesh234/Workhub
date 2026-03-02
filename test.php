<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Cloudinary Debug</h2>";

// Load config
require_once __DIR__ . '/config/Config.php';

// Get values
$cloudName = Config::get('CLOUDINARY_CLOUD_NAME');
$apiKey = Config::get('CLOUDINARY_API_KEY');
$apiSecret = Config::get('CLOUDINARY_API_SECRET');

// Show results
echo "Cloud Name: " . ($cloudName ?: ' EMPTY') . "<br>";
echo "API Key: " . ($apiKey ?: 'EMPTY') . "<br>";
echo "API Secret: " . ($apiSecret ? ' SET (' . strlen($apiSecret) . ' chars)' : ' EMPTY') . "<br><br>";

// Try loading Cloudinary
if ($cloudName && $apiKey && $apiSecret) {
    echo "Attempting to load Cloudinary...<br>";
    try {
        require_once __DIR__ . '/config/cloudinary.php';
        require_once __DIR__ . '/src/Service/CloudinaryService.php';
        $service = new CloudinaryService();
        echo "SUCCESS! Cloudinary is configured correctly!";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
} else {
    echo " Config values are missing!";
}