<?php

namespace App\Services\Paypal;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\AppSetting;

class ModernPayPalService
{
    protected $provider;
    protected $mode;
    protected $sandboxClientId;
    protected $sandboxClientSecret;
    protected $liveClientId;
    protected $liveClientSecret;
    protected $liveAppId;

    public function __construct()
    {
        // Load settings from database
        $this->loadSettings();
        
        $this->mode = config('paypal.mode', 'sandbox');

        $this->provider = new PayPalClient;

        $config = [
            'mode' => $this->mode,
            'sandbox' => [
                'client_id' => $this->sandboxClientId,
                'client_secret' => $this->sandboxClientSecret,
                'app_id' => 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => $this->liveClientId,
                'client_secret' => $this->liveClientSecret,
                'app_id' => $this->liveAppId ?? 'APP-80W284485P519543T',
            ],
            'payment_action' => 'Sale',
            'currency' => 'USD',
            'notify_url' => '',
            'locale' => 'en_US',
            'validate_ssl' => true,
        ];

        $this->provider->setApiCredentials($config);
        
        // Only get access token if credentials exist
        if ($this->hasValidCredentials()) {
            $this->provider->getAccessToken();
        }

        Log::info('Modern PayPal Service initialized', [
            'mode' => $this->mode,
            'has_sandbox_client_id' => !empty($this->sandboxClientId),
            'has_sandbox_secret' => !empty($this->sandboxClientSecret),
            'has_live_client_id' => !empty($this->liveClientId),
            'has_live_secret' => !empty($this->liveClientSecret)
        ]);
    }

    /**
     * Load PayPal settings from database
     */
    private function loadSettings(): void
    {
        try {
            // Load sandbox credentials
            $sandboxClientId = AppSetting::where('group', 'paypal')
                ->where('key', 'PAYPAL_SANDBOX_CLIENT_ID')
                ->first();
            
            $this->sandboxClientId = $sandboxClientId ? $sandboxClientId->value : null;
            
            $sandboxClientSecret = AppSetting::where('group', 'paypal')
                ->where('key', 'PAYPAL_SANDBOX_CLIENT_SECRET')
                ->first();
            
            $this->sandboxClientSecret = $sandboxClientSecret ? $sandboxClientSecret->value : null;
            
            // Load live credentials
            $liveClientId = AppSetting::where('group', 'paypal')
                ->where('key', 'PAYPAL_LIVE_CLIENT_ID')
                ->first();
            
            $this->liveClientId = $liveClientId ? $liveClientId->value : null;
            
            $liveClientSecret = AppSetting::where('group', 'paypal')
                ->where('key', 'PAYPAL_LIVE_CLIENT_SECRET')
                ->first();
            
            $this->liveClientSecret = $liveClientSecret ? $liveClientSecret->value : null;
            
            // Load live app ID (optional)
            $liveAppId = AppSetting::where('group', 'paypal')
                ->where('key', 'PAYPAL_LIVE_APP_ID')
                ->first();
            
            $this->liveAppId = $liveAppId ? $liveAppId->value : null;
            
            Log::info('PayPal settings loaded from database', [
                'has_sandbox_credentials' => !empty($this->sandboxClientId) && !empty($this->sandboxClientSecret),
                'has_live_credentials' => !empty($this->liveClientId) && !empty($this->liveClientSecret)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load PayPal settings from database', [
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
     * Create order
     */
    public function createOrder(
        $amount,
        $currency = 'USD',
        $description = 'Subscription Payment',
        ?string $returnUrl = null,
        ?string $cancelUrl = null
    ) {
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
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

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: Invalid response',
                'error' => $response
            ];
        } catch (\Exception $e) {
            Log::error('PayPal create order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'mode' => $this->mode
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: ' . $this->getUserFriendlyError($e),
                'error' => $e->getMessage()
            ];
        }
    }

    public function captureOrder($orderId)
    {
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
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

            return [
                'success' => false,
                'message' => 'Payment capture failed: ' . ($response['status'] ?? 'Unknown status'),
                'response' => $response
            ];
        } catch (\Exception $e) {
            Log::error('PayPal capture order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'mode' => $this->mode
            ]);

            return [
                'success' => false,
                'message' => 'Failed to capture PayPal order: ' . $this->getUserFriendlyError($e),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getOrder($orderId)
    {
        try {
            // Check if credentials are configured
            if (!$this->hasValidCredentials()) {
                Log::error('PayPal credentials not configured for ' . $this->mode . ' mode');
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
        } catch (\Exception $e) {
            Log::error('PayPal get order error: ' . $e->getMessage(), [
                'mode' => $this->mode
            ]);

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
                'app_id' => 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => $this->liveClientId,
                'client_secret' => $this->liveClientSecret,
                'app_id' => $this->liveAppId ?? 'APP-80W284485P519543T',
            ],
            'payment_action' => 'Sale',
            'currency' => 'USD',
            'notify_url' => '',
            'locale' => 'en_US',
            'validate_ssl' => true,
        ];

        $this->provider->setApiCredentials($config);
        
        if ($this->hasValidCredentials()) {
            $this->provider->getAccessToken();
        }
        
        Log::info('PayPal settings reloaded', [
            'mode' => $this->mode,
            'has_credentials' => $this->hasValidCredentials()
        ]);
    }

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
        } catch (\Exception $e) {
            // Fall through to default message
        }

        return $message;
    }
}