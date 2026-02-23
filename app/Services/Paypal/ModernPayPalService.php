<?php
namespace App\Services\Paypal;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ModernPayPalService
{
    protected $client;
    protected $environment;
    protected $mode;

    public function __construct()
    {
        $this->mode = config('paypal.mode', 'sandbox');
        
        // Get credentials
        $clientId = config('paypal.client_id');
        $clientSecret = config('paypal.secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('PayPal credentials not configured');
        }

        // Set up environment based on mode
        if ($this->mode === 'live') {
            $this->environment = new ProductionEnvironment($clientId, $clientSecret);
        } else {
            $this->environment = new SandboxEnvironment($clientId, $clientSecret);
        }

        // Create PayPal HTTP client
        $this->client = new PayPalHttpClient($this->environment);

        Log::info('Modern PayPal Service initialized', [
            'mode' => $this->mode,
            'client_id_exists' => !empty($clientId)
        ]);
    }

    /**
     * Create an order for subscription payment
     */
    public function createOrder($amount, $currency = 'USD', $description = 'Subscription Payment')
    {
        try {
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            
            // Generate unique reference ID
            $referenceId = 'SUB-' . Str::random(8) . '-' . time();

            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $referenceId,
                        'description' => $description,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'custom_id' => json_encode([
                            'plan_id' => Session::get('pending_subscription.plan_id'),
                            'billing_type' => Session::get('pending_subscription.billing_type'),
                            'user_id' => Session::get('pending_subscription.user_id')
                        ])
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => route('merchant.subscription.paypal.success'),
                    'cancel_url' => route('merchant.subscription.paypal.cancel'),
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            // Create order
            $response = $this->client->execute($request);

            Log::info('PayPal order created', [
                'order_id' => $response->result->id,
                'status' => $response->result->status
            ]);

            // Find approval URL
            $approvalUrl = null;
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            return [
                'success' => true,
                'order_id' => $response->result->id,
                'approval_url' => $approvalUrl,
                'status' => $response->result->status,
                'response' => $response->result
            ];

        } catch (\Exception $e) {
            Log::error('PayPal create order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create PayPal order: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Capture payment for an order
     */
    public function captureOrder($orderId)
    {
        try {
            $request = new OrdersCaptureRequest($orderId);
            $response = $this->client->execute($request);

            Log::info('PayPal order captured', [
                'order_id' => $orderId,
                'status' => $response->result->status,
                'capture_id' => $response->result->purchase_units[0]->payments->captures[0]->id ?? null
            ]);

            // Extract capture details
            $capture = null;
            if (isset($response->result->purchase_units[0]->payments->captures[0])) {
                $capture = $response->result->purchase_units[0]->payments->captures[0];
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'capture_id' => $capture->id ?? null,
                'status' => $response->result->status,
                'amount' => $capture->amount->value ?? null,
                'currency' => $capture->amount->currency_code ?? null,
                'response' => $response->result
            ];

        } catch (\Exception $e) {
            Log::error('PayPal capture order error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to capture PayPal order: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId)
    {
        try {
            $request = new OrdersGetRequest($orderId);
            $response = $this->client->execute($request);

            return [
                'success' => true,
                'order' => $response->result,
                'status' => $response->result->status
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
}