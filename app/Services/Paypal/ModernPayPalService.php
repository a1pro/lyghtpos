<?php

namespace App\Services\Paypal;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Exception;

class ModernPayPalService
{
    protected $provider;
    protected $mode;
    protected $sandboxClientId;
    protected $sandboxClientSecret;
    protected $sandboxAppId;
    protected $liveClientId;
    protected $liveClientSecret;
    protected $liveAppId;
    protected $paymentAction;
    protected $currency;
    protected $notifyUrl;
    protected $locale;
    protected $validateSsl;
    protected $mockMode;
    protected $fallbackToMock;

    public function __construct()
    {
        // Load settings from config
        $this->loadSettings();
        
        $this->provider = new PayPalClient;

        $config = [
            'mode' => $this->mode,
            'sandbox' => [
                'client_id' => $this->sandboxClientId,
                'client_secret' => $this->sandboxClientSecret,
                'app_id' => $this->sandboxAppId ?? 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => $this->liveClientId,
                'client_secret' => $this->liveClientSecret,
                'app_id' => $this->liveAppId ?? 'APP-80W284485P519543T',
            ],
            'payment_action' => $this->paymentAction,
            'currency' => $this->currency,
            'notify_url' => $this->notifyUrl,
            'locale' => $this->locale,
            'validate_ssl' => $this->validateSsl,
        ];

        $this->provider->setApiCredentials($config);
        
        // Only get access token if credentials exist
        if ($this->hasValidCredentials() && !$this->mockMode) {
            $this->provider->getAccessToken();
        }

        Log::info('Modern PayPal Service initialized', [
            'mode' => $this->mode,
            'has_sandbox_client_id' => !empty($this->sandboxClientId),
            'has_sandbox_secret' => !empty($this->sandboxClientSecret),
            'has_live_client_id' => !empty($this->liveClientId),
            'has_live_secret' => !empty($this->liveClientSecret),
            'mock_mode' => $this->mockMode
        ]);
    }

    /**
     * Load PayPal settings from config
     * This will get values from database (if available) or fall back to .env from services.php
     */
    private function loadSettings(): void
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
            $this->mode = $getStringValue('services.paypal.mode', 'sandbox');
            
            // Sandbox credentials
            $this->sandboxClientId = $getStringValue('services.paypal.sandbox.client_id');
            $this->sandboxClientSecret = $getStringValue('services.paypal.sandbox.client_secret');
            $this->sandboxAppId = $getStringValue('services.paypal.sandbox.app_id', 'APP-80W284485P519543T');
            
            // Live credentials
            $this->liveClientId = $getStringValue('services.paypal.live.client_id');
            $this->liveClientSecret = $getStringValue('services.paypal.live.client_secret');
            $this->liveAppId = $getStringValue('services.paypal.live.app_id', 'APP-80W284485P519543T');
            
            // Other settings
            $this->paymentAction = $getStringValue('services.paypal.payment_action', 'Sale');
            $this->currency = $getStringValue('services.paypal.currency', 'USD');
            $this->notifyUrl = $getStringValue('services.paypal.notify_url', '');
            $this->locale = $getStringValue('services.paypal.locale', 'en_US');
            $this->validateSsl = $getBooleanValue('services.paypal.validate_ssl', true);
            
            // Mock settings
            $this->mockMode = $getBooleanValue('services.paypal.mock_mode', false);
            $this->fallbackToMock = $getBooleanValue('services.paypal.fallback_to_mock', true);
            
            Log::info('PayPal settings loaded from config', [
                'mode' => $this->mode,
                'has_sandbox_credentials' => !empty($this->sandboxClientId) && !empty($this->sandboxClientSecret),
                'has_live_credentials' => !empty($this->liveClientId) && !empty($this->liveClientSecret),
                'mock_mode' => $this->mockMode,
                'fallback_to_mock' => $this->fallbackToMock
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to load PayPal settings', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if valid credentials exist for current mode
     */
    private function hasValidCredentials(): bool
    {
        if ($this->mode === 'sandbox') {
            return !empty($this->sandboxClientId) && !empty($this->sandboxClientSecret);
        } else {
            return !empty($this->liveClientId) && !empty($this->liveClientSecret);
        }
    }

    /**
     * Check if mock mode is enabled
     */
    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Create order
     */
    public function createOrder(
        $amount,
        $currency = 'USD',
        $description = 'Subscription Payment',
        ?string $returnUrl = null,
        ?string $cancelUrl = null
    ) {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockCreateOrder($amount, $currency, $description, $returnUrl, $cancelUrl);
        }
        
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
                
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock PayPal order creation');
                    return $this->mockCreateOrder($amount, $currency, $description, $returnUrl, $cancelUrl);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment gateway is not properly configured. Please contact administrator.'
                ];
            }

            $referenceId = 'SUB-' . Str::random(8) . '-' . time();

            $pendingSubscription = Session::get('pending_subscription', []);

            $customData = json_encode([
                'plan_id' => $pendingSubscription['plan_id'] ?? null,
                'billing_type' => $pendingSubscription['billing_type'] ?? null,
                'user_id' => $pendingSubscription['user_id'] ?? null,
                'timestamp' => now()->timestamp
            ]);

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $referenceId,
                        'description' => $description,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'custom_id' => $customData
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => $returnUrl ?: route('merchant.subscription.paypal.success'),
                    'cancel_url' => $cancelUrl ?: route('merchant.subscription.paypal.cancel'),
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            $response = $this->provider->createOrder($orderData);

            Log::info('PayPal order created', [
                'order_id' => $response['id'] ?? null,
                'status' => $response['status'] ?? null,
                'mode' => $this->mode,
                'return_url' => $orderData['application_context']['return_url'],
                'cancel_url' => $orderData['application_context']['cancel_url'],
            ]);

            if (isset($response['id']) && $response['id']) {
                $approvalUrl = null;
                foreach ($response['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        $approvalUrl = $link['href'];
                        break;
                    }
                }

                return [
                    'success' => true,
                    'order_id' => $response['id'],
                    'approval_url' => $approvalUrl,
                    'status' => $response['status'],
                    'response' => $response
                ];
            }

            if ($this->fallbackToMock) {
                return $this->mockCreateOrder($amount, $currency, $description, $returnUrl, $cancelUrl);
            }

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: Invalid response',
                'error' => $response
            ];
        } catch (Exception $e) {
            Log::error('PayPal create order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'mode' => $this->mode
            ]);

            if ($this->fallbackToMock) {
                return $this->mockCreateOrder($amount, $currency, $description, $returnUrl, $cancelUrl);
            }

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: ' . $this->getUserFriendlyError($e),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Capture order
     */
    public function captureOrder($orderId)
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockCaptureOrder($orderId);
        }
        
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
                
                if ($this->fallbackToMock) {
                    return $this->mockCaptureOrder($orderId);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment gateway is not properly configured. Please contact administrator.'
                ];
            }

            $response = $this->provider->capturePaymentOrder($orderId);

            Log::info('PayPal order captured', [
                'order_id' => $orderId,
                'status' => $response['status'] ?? null,
                'capture_id' => $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                'mode' => $this->mode
            ]);

            if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                $capture = $response['purchase_units'][0]['payments']['captures'][0] ?? null;

                $payerEmail = $response['payer']['email_address'] ?? null;
                $payerName = null;

                if (isset($response['payer']['name'])) {
                    $payerName = ($response['payer']['name']['given_name'] ?? '') . ' ' . ($response['payer']['name']['surname'] ?? '');
                    $payerName = trim($payerName);
                }

                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'capture_id' => $capture['id'] ?? null,
                    'status' => $response['status'],
                    'amount' => $capture['amount']['value'] ?? null,
                    'currency' => $capture['amount']['currency_code'] ?? null,
                    'payer_email' => $payerEmail,
                    'payer_name' => $payerName ?: null,
                    'response' => $response
                ];
            }

            if ($this->fallbackToMock) {
                return $this->mockCaptureOrder($orderId);
            }

            return [
                'success' => false,
                'message' => 'Payment capture failed: ' . ($response['status'] ?? 'Unknown status'),
                'response' => $response
            ];
        } catch (Exception $e) {
            Log::error('PayPal capture order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'mode' => $this->mode
            ]);

            if ($this->fallbackToMock) {
                return $this->mockCaptureOrder($orderId);
            }

            return [
                'success' => false,
                'message' => 'Failed to capture PayPal order: ' . $this->getUserFriendlyError($e),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId)
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetOrder($orderId);
        }
        
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
                
                if ($this->fallbackToMock) {
                    return $this->mockGetOrder($orderId);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment gateway is not properly configured. Please contact administrator.'
                ];
            }

            $response = $this->provider->showOrderDetails($orderId);

            return [
                'success' => true,
                'order' => $response,
                'status' => $response['status'] ?? null
            ];
        } catch (Exception $e) {
            Log::error('PayPal get order error: ' . $e->getMessage(), [
                'mode' => $this->mode
            ]);

            if ($this->fallbackToMock) {
                return $this->mockGetOrder($orderId);
            }

            return [
                'success' => false,
                'message' => 'Failed to get order details',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if PayPal is configured
     */
    public function isConfigured(): bool
    {
        return $this->hasValidCredentials();
    }

    /**
     * Reload settings (useful after updating settings)
     */
    public function reloadSettings(): void
    {
        $this->loadSettings();
        
        // Reinitialize provider with new settings
        $config = [
            'mode' => $this->mode,
            'sandbox' => [
                'client_id' => $this->sandboxClientId,
                'client_secret' => $this->sandboxClientSecret,
                'app_id' => $this->sandboxAppId ?? 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => $this->liveClientId,
                'client_secret' => $this->liveClientSecret,
                'app_id' => $this->liveAppId ?? 'APP-80W284485P519543T',
            ],
            'payment_action' => $this->paymentAction,
            'currency' => $this->currency,
            'notify_url' => $this->notifyUrl,
            'locale' => $this->locale,
            'validate_ssl' => $this->validateSsl,
        ];

        $this->provider->setApiCredentials($config);
        
        if ($this->hasValidCredentials() && !$this->mockMode) {
            $this->provider->getAccessToken();
        }
        
        Log::info('PayPal settings reloaded', [
            'mode' => $this->mode,
            'has_credentials' => $this->hasValidCredentials(),
            'mock_mode' => $this->mockMode
        ]);
    }

    /**
     * Debug method to check current configuration
     */
    public function debugConfig(): array
    {
        return [
            'mode' => $this->mode,
            'has_sandbox_credentials' => !empty($this->sandboxClientId) && !empty($this->sandboxClientSecret),
            'has_live_credentials' => !empty($this->liveClientId) && !empty($this->liveClientSecret),
            'sandbox_client_id' => $this->sandboxClientId ? substr($this->sandboxClientId, 0, 10) . '***' : null,
            'live_client_id' => $this->liveClientId ? substr($this->liveClientId, 0, 10) . '***' : null,
            'currency' => $this->currency,
            'payment_action' => $this->paymentAction,
            'mock_mode' => $this->mockMode,
            'fallback_to_mock' => $this->fallbackToMock,
        ];
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyError($exception)
    {
        $message = 'Payment processing failed. Please try again.';

        try {
            $errorMessage = $exception->getMessage();

            if (strpos($errorMessage, 'INSTRUMENT_DECLINED') !== false) {
                return 'Your payment method was declined. Please try another payment method.';
            }
            if (strpos($errorMessage, 'PAYER_ACCOUNT_LOCKED') !== false) {
                return 'Your PayPal account is locked or closed. Please contact PayPal support.';
            }
            if (strpos($errorMessage, 'PAYER_ACCOUNT_RESTRICTED') !== false) {
                return 'Your PayPal account has restrictions. Please contact PayPal support.';
            }
            if (strpos($errorMessage, 'PAYER_CANNOT_PAY') !== false) {
                return 'You cannot complete this payment. Please contact PayPal support.';
            }
            if (strpos($errorMessage, 'TRANSACTION_REFUSED') !== false) {
                return 'The transaction was refused. Please try again later.';
            }
            if (strpos($errorMessage, 'CURRENCY_NOT_SUPPORTED') !== false) {
                return 'The selected currency is not supported.';
            }
            if (strpos($errorMessage, 'DUPLICATE_INVOICE_ID') !== false) {
                return 'A duplicate transaction was detected. Please try again.';
            }
            if (strpos($errorMessage, 'PERMISSION_DENIED') !== false) {
                return 'Permission denied. Please check your PayPal credentials.';
            }
            if (strpos($errorMessage, 'INVALID_CLIENT') !== false) {
                return 'Invalid PayPal client configuration. Please contact administrator.';
            }

            if (method_exists($exception, 'getMessage')) {
                $message = $exception->getMessage();
            }
        } catch (Exception $e) {
            // Fall through to default message
        }

        return $message;
    }

    /**
     * Mock methods for testing
     */
    protected function mockCreateOrder($amount, $currency = 'USD', $description = 'Subscription Payment', ?string $returnUrl = null, ?string $cancelUrl = null): array
    {
        Log::info('Mock: Creating PayPal order', [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description
        ]);

        $mockOrderId = 'MOCK-ORDER-' . Str::random(8) . '-' . time();

        return [
            'success' => true,
            'order_id' => $mockOrderId,
            'approval_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $mockOrderId,
            'status' => 'CREATED',
            'response' => [
                'id' => $mockOrderId,
                'status' => 'CREATED',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $mockOrderId]
                ]
            ],
            'mock' => true
        ];
    }

    protected function mockCaptureOrder($orderId): array
    {
        Log::info('Mock: Capturing PayPal order', ['order_id' => $orderId]);

        $mockCaptureId = 'MOCK-CAPTURE-' . Str::random(8) . '-' . time();

        return [
            'success' => true,
            'order_id' => $orderId,
            'capture_id' => $mockCaptureId,
            'status' => 'COMPLETED',
            'amount' => '10.00',
            'currency' => 'USD',
            'payer_email' => 'mock-buyer@example.com',
            'payer_name' => 'Mock Buyer',
            'response' => [
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'captures' => [
                                [
                                    'id' => $mockCaptureId,
                                    'amount' => [
                                        'value' => '10.00',
                                        'currency_code' => 'USD'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'payer' => [
                    'email_address' => 'mock-buyer@example.com',
                    'name' => [
                        'given_name' => 'Mock',
                        'surname' => 'Buyer'
                    ]
                ]
            ],
            'mock' => true
        ];
    }

    protected function mockGetOrder($orderId): array
    {
        Log::info('Mock: Getting PayPal order', ['order_id' => $orderId]);

        return [
            'success' => true,
            'order' => [
                'id' => $orderId,
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'amount' => [
                            'value' => '10.00',
                            'currency_code' => 'USD'
                        ]
                    ]
                ]
            ],
            'status' => 'COMPLETED',
            'mock' => true
        ];
    }
}