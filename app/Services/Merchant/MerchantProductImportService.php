<?php

namespace App\Services\Merchant;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MerchantProductImportService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;
    protected string $currencyCode;
    protected string $currencySymbol;
    protected string $currencyName;
    protected string $adminUserId;

    public function __construct()
    {
        $this->loadSettings();
        $this->validateRequiredConfig();

        Log::info('MerchantProductService initialized', [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'admin_user_id' => $this->adminUserId,
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Load all Cyclos settings from config
     * This will get values from database (if available) or fall back to .env from services.php
     */
    protected function loadSettings(): void
    {
        try {
            // Helper function to safely get string value from config
            $getStringValue = function ($key, $default = '') {
                $value = config($key, $default);

                // If it's an array, try to get the first value
                if (is_array($value)) {
                    $value = reset($value);
                }

                return $value !== null ? (string) $value : $default;
            };

            // Helper function to safely get boolean value from config
            $getBooleanValue = function ($key, $default = false) {
                $value = config($key, $default);

                // If it's an array, try to get the first value
                if (is_array($value)) {
                    $value = reset($value);
                }

                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            };

            // Load all settings from config
            $this->baseUrl = rtrim($getStringValue('services.cyclos.base_url', ''), '/');
            $this->username = $getStringValue('services.cyclos.username', '');
            $this->password = $getStringValue('services.cyclos.password', '');
            $this->channel = $getStringValue('services.cyclos.channel', 'webServices');
            $this->currencyCode = $getStringValue('services.cyclos.currency_code', 'TT');
            $this->currencySymbol = $getStringValue('services.cyclos.currency_symbol', 'TT');
            $this->currencyName = $getStringValue('services.cyclos.currency_name', 'TradeTokens');
            $this->adminUserId = $getStringValue('services.cyclos.admin_user_id', '');

            Log::info('MerchantProductService settings loaded from config', [
                'base_url' => $this->baseUrl,
                'username' => $this->username,
                'has_password' => !empty($this->password),
                'admin_user_id' => $this->adminUserId,
                'currency' => $this->currencyName,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load MerchantProductService settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load MerchantProductService configuration: ' . $e->getMessage());
        }
    }

    /**
     * Validate required configuration
     */
    protected function validateRequiredConfig(): void
    {
        $missing = [];

        if (empty($this->baseUrl)) {
            $missing[] = 'services.cyclos.base_url';
        }

        if (empty($this->username)) {
            $missing[] = 'services.cyclos.username';
        }

        if (empty($this->password)) {
            $missing[] = 'services.cyclos.password';
        }

        if (empty($this->adminUserId)) {
            $missing[] = 'services.cyclos.admin_user_id';
        }

        if (!empty($missing)) {
            Log::error('Missing required Cyclos configuration', [
                'missing' => $missing
            ]);
            throw new \RuntimeException(
                'Missing required Cyclos configuration: ' . implode(', ', $missing)
            );
        }
    }

    public function upload($file)
    {
        $url = $this->baseUrl . '/api/imported-files/ads';

        $user = Auth::user();
        $cyclosUsername = $user->cyclos_username;

        if (empty($cyclosUsername)) {
            Log::error('Product Import Service: Authenticated user does not have a Cyclos username', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            throw new \Exception('Your account is not properly configured for product imports. Please contact support.');
        }

        Log::info('Uploading file to Cyclos', [
            'url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientMimeType(),
        ]);

        $processedFilePath = $this->prepareCsv($file, $cyclosUsername);

        Log::info('Prepared CSV file for upload', [
            'processed_file_path' => $processedFilePath,
            'original_file_name' => $file->getClientOriginalName(),
        ]);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Channel' => $this->channel,
            ])->attach(
                'file',
                file_get_contents($processedFilePath),
                basename($processedFilePath)
            )->post($url, [
                'params' => json_encode([
                    'currency' => 'unit',
                    'processAutomatically' => true
                ])
            ]);

        // cleanup temp file
        @unlink($processedFilePath);

        Log::info('Upload response from Cyclos', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            Log::error('Failed to upload file to Cyclos', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Upload failed');
        }

        return [
            'id' => trim($response->body())
        ];
    }

    public function getImportDetails($id)
    {
        $url = $this->baseUrl . "/api/imported-file/{$id}";

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Channel' => $this->channel,
            ])->get($url);

        Log::info('Get import details response from Cyclos', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            Log::error('Failed to fetch import details from Cyclos', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Failed to fetch import details');
        }

        return $response->json();
    }

    private function prepareCsv($file, $cyclosUsername)
    {
        $inputPath = $file->getRealPath();
        $outputPath = storage_path('app/temp_import_' . time() . '.csv');

        $input = fopen($inputPath, 'r');
        $output = fopen($outputPath, 'w');

        $header = fgetcsv($input);

        // Normalize header keys
        $header = array_map('trim', $header);

        // Check columns
        $hasUser = in_array('user', $header);
        $hasTradeType = in_array('field.tradeType', $header);

        if (!$hasUser) {
            $header[] = 'user';
        }

        if (!$hasTradeType) {
            $header[] = 'field.tradeType';
        }

        // Write updated header
        fputcsv($output, $header);

        // Process rows
        while (($row = fgetcsv($input)) !== false) {

            if (!$hasUser) {
                $row[] = $cyclosUsername;
            }

            if (!$hasTradeType) {
                $row[] = 'tradeTypeDemand';
            }

            fputcsv($output, $row);
        }

        Log::info('CSV file processed for Cyclos import', [
            'original_file' => $inputPath,
            'processed_file' => $outputPath,
            'added_columns' => [
                'user' => !$hasUser,
                'field.tradeType' => !$hasTradeType
            ]
        ]);

        fclose($input);
        fclose($output);

        return $outputPath;
    }
}
