<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DatabaseConfigServiceProvider extends ServiceProvider
{
    protected $services = [
        'cyclos',
        'paypal',
        'stripe',
    ];
    
    protected $keyMappings = [
        'cyclos' => [
            'CYCLOS_BASE_URL' => 'base_url',
            'CYCLOS_API_USERNAME' => 'username',
            'CYCLOS_API_PASSWORD' => 'password',
            'CYCLOS_DEFAULT_GROUP' => 'default_group',
            'CYCLOS_ACCEPT_AGREEMENT' => 'agreement',
            'CYCLOS_MOCK_MODE' => 'mock_mode',
            'CYCLOS_FALLBACK_TO_MOCK' => 'fallback_to_mock',
            'CYCLOS_USE_API_PASSWORD' => 'use_api_password',
            'CYCLOS_CHANNEL' => 'channel',
            'CYCLOS_PAYMENT_TYPE_ID' => 'payment_type_id',
            'CYCLOS_ADMIN_USER_ID' => 'admin_user_id',
            'CYCLOS_CURRENCY_CODE' => 'currency_code',
            'CYCLOS_CURRENCY_SYMBOL' => 'currency_symbol',
            'CYCLOS_CURRENCY_NAME' => 'currency_name',
        ],
        'paypal' => [
            'PAYPAL_MODE' => 'mode',
            'PAYPAL_SANDBOX_CLIENT_ID' => 'sandbox.client_id',
            'PAYPAL_SANDBOX_CLIENT_SECRET' => 'sandbox.client_secret',
            'PAYPAL_SANDBOX_APP_ID' => 'sandbox.app_id',
            'PAYPAL_LIVE_CLIENT_ID' => 'live.client_id',
            'PAYPAL_LIVE_CLIENT_SECRET' => 'live.client_secret',
            'PAYPAL_LIVE_APP_ID' => 'live.app_id',
            'PAYPAL_PAYMENT_ACTION' => 'payment_action',
            'PAYPAL_CURRENCY' => 'currency',
            'PAYPAL_NOTIFY_URL' => 'notify_url',
            'PAYPAL_LOCALE' => 'locale',
            'PAYPAL_VALIDATE_SSL' => 'validate_ssl',
            'PAYPAL_MOCK_MODE' => 'mock_mode',
            'PAYPAL_FALLBACK_TO_MOCK' => 'fallback_to_mock',
        ],
        'stripe' => [
            'STRIPE_KEY' => 'key',
            'STRIPE_SECRET' => 'secret',
            'STRIPE_WEBHOOK_SECRET' => 'webhook.secret',
            'STRIPE_CURRENCY' => 'currency',
        ],
        'authorize' => [
            'AUTHORIZE_API_LOGIN_ID' => 'api_login_id',
            'AUTHORIZE_TRANSACTION_KEY' => 'transaction_key',
            'AUTHORIZE_SANDBOX_MODE' => 'sandbox_mode',
            'AUTHORIZE_CURRENCY' => 'currency',
            'AUTHORIZE_MOCK_MODE' => 'mock_mode',
            'AUTHORIZE_FALLBACK_TO_MOCK' => 'fallback_to_mock',
        ],
    ];
    
    public function boot(): void
    {
        try {
            foreach ($this->services as $service) {
                $this->loadServiceConfig($service);
            }
        } catch (\Exception $e) {
            Log::error('Failed to load database configurations', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    protected function loadServiceConfig(string $service): void
    {
        try {
            $settings = AppSetting::where('group', $service)->get();
            
            if ($settings->isEmpty()) {
                return;
            }
            
            // Get current config from services.php
            $currentConfig = config("services.{$service}", []);
            
            // Build config array from database
            $dbConfig = [];
            $mappings = $this->keyMappings[$service] ?? [];
            
            foreach ($settings as $setting) {
                $configKey = $mappings[$setting->key] ?? $this->transformKey($setting->key);
                $value = $setting->value;
                
                // Handle boolean values
                if (in_array($value, ['true', 'false', '1', '0'])) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                
                $this->setNestedValue($dbConfig, $configKey, $value);
            }
            
            // IMPORTANT: Use array_replace_recursive instead of array_merge_recursive
            // This will replace values instead of merging them into arrays
            $mergedConfig = array_replace_recursive($currentConfig, $dbConfig);
            
            // Set the final config
            config(["services.{$service}" => $mergedConfig]);
            
        } catch (\Exception $e) {
            Log::error("Failed to load configuration for service: {$service}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    protected function transformKey(string $key): string
    {
        $parts = explode('_', $key, 2);
        $keyWithoutPrefix = $parts[1] ?? $key;
        return str_replace('_', '.', strtolower($keyWithoutPrefix));
    }
    
    protected function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }
    
    public function register(): void
    {
        //
    }
}