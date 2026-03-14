<?php

namespace App\Services\Paypal;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ModernPayPalService
{
    protected $provider;
    protected $mode;

    public function __construct()
    {
        $this->mode = config('paypal.mode', 'sandbox');

        $this->provider = new PayPalClient;

        $config = [
            'mode' => $this->mode,
            'sandbox' => [
                'client_id' => config('paypal.sandbox.client_id'),
                'client_secret' => config('paypal.sandbox.client_secret'),
                'app_id' => 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => config('paypal.live.client_id'),
                'client_secret' => config('paypal.live.client_secret'),
                'app_id' => config('paypal.live.app_id'),
            ],
            'payment_action' => 'Sale',
            'currency' => 'USD',
            'notify_url' => '',
            'locale' => 'en_US',
            'validate_ssl' => true,
        ];

        $this->provider->setApiCredentials($config);
        $this->provider->getAccessToken();

        Log::info('Modern PayPal Service initialized', [
            'mode' => $this->mode,
            'client_id_exists' => !empty(config('paypal.sandbox.client_id'))
        ]);
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
                'trace' => $e->getTraceAsString()
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
            $response = $this->provider->capturePaymentOrder($orderId);

            Log::info('PayPal order captured', [
                'order_id' => $orderId,
                'status' => $response['status'] ?? null,
                'capture_id' => $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? null
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
                'trace' => $e->getTraceAsString()
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
            $response = $this->provider->showOrderDetails($orderId);

            return [
                'success' => true,
                'order' => $response,
                'status' => $response['status'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('PayPal get order error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get order details',
                'error' => $e->getMessage()
            ];
        }
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

            if (method_exists($exception, 'getMessage')) {
                $message = $exception->getMessage();
            }
        } catch (\Exception $e) {
        }

        return $message;
    }
}