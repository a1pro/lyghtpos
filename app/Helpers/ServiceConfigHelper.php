<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ServiceConfigHelper
{
    /**
     * Get configuration value for a service
     */
    public static function get(string $service, string $key, $default = null)
    {
        $configKey = "services.{$service}.{$key}";
        $value = Config::get($configKey);
        
        if ($value !== null && $value !== '') {
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Get all configuration for a service
     */
    public static function getAll(string $service): array
    {
        return Config::get("services.{$service}", []);
    }
    
    /**
     * Check if service has required configuration
     */
    public static function hasValidConfig(string $service, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            $value = self::get($service, $key);
            if (empty($value)) {
                Log::warning("Missing required config for {$service}: {$key}");
                return false;
            }
        }
        
        return true;
    }
}