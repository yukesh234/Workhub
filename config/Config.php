<?php
// config/Config.php

class Config {
    private static $config = [];
    
    public static function load() {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            die('Error: .env file not found!');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
    }
    
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
}

Config::load();