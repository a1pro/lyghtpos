<?php

namespace App\Services\Api\POS\Customer;

use App\Models\UserJoiningFees;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PosPayPalPaymentService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $currency;
    protected $mode;

    public function __construct()
    {
        $this->mode = config('services.paypal.mode', 'sandbox');
        $this->currency = config('services.paypal.currency', 'USD');
        
        // Set credentials based on mode
        if ($this->mode === 'live') {
            $this->clientId = config('services.paypal.live.client_id');
            $this->clientSecret = config('services.paypal.live.client_secret');
            $this->baseUrl = 'https://api.paypal.com';
        } else {
            $this->clientId = config('services.paypal.sandbox.client_id');
            $this->clientSecret = config('services.paypal.sandbox.client_secret');
            $this->baseUrl = 'https://api.sandbox.paypal.com';
        }

        // Validate credentials
        if (!$this->clientId || !$this->clientSecret) {
            Log::error('PayPal credentials not configured properly');
        }
    }

    /**
     * Get PayPal access token
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $token = $response->json()['access_token'];
                Log::info('PayPal access token retrieved successfully');
                return $token;
            }

            $error = 'Failed to get PayPal access token: ' . $response->body();
            Log::error($error);
            throw new \Exception($error);
            
        } catch (\Exception $e) {
            Log::error('PayPal token error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a PayPal order for Mobile App
     */
    public function createOrder($amount, $userId)
    {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid amount provided'
                ];
            }

            $accessToken = $this->getAccessToken();
            
            // Format amount to 2 decimal places
            $formattedAmount = number_format($amount, 2, '.', '');

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'user_' . $userId . '_' . time(),
                        'description' => 'Joining Fees Payment',
                        'amount' => [
                            'currency_code' => $this->currency,
                            'value' => $formattedAmount,
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => $this->currency,
                                    'value' => $formattedAmount
                                ]
                            ]
                        ],
                        'items' => [
                            [
                                'name' => 'Joining Fees',
                                'description' => 'Payment for joining fees',
                                'unit_amount' => [
                                    'currency_code' => $this->currency,
                                    'value' => $formattedAmount
                                ],
                                'quantity' => '1',
                                'category' => 'DIGITAL_GOODS'
                            ]
                        ],
                        'custom_id' => json_encode([
                            'user_id' => $userId,
                            'amount' => $amount
                        ])
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name', 'My App'),
                    'locale' => 'en-US',  // FIXED: Changed from 'en_US' to 'en-US'
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            Log::info('Creating PayPal order for user: ' . $userId . ', amount: ' . $amount);
            Log::debug('PayPal order data: ' . json_encode($orderData));

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'PayPal-Request-Id' => uniqid() . '_' . $userId
                ])
                ->post($this->baseUrl . '/v2/checkout/orders', $orderData);

            if ($response->successful()) {
                $orderData = $response->json();
                
                // Create a pending record in user_joining_fees table
                $userJoiningFee = UserJoiningFees::create([
                    'user_id' => $userId,
                    'amount' => $amount,
                    'transaction_id' => $orderData['id'],
                    'status' => 'pending'
                ]);

                Log::info('PayPal order created successfully. Order ID: ' . $orderData['id']);

                return [
                    'success' => true,
                    'order_id' => $orderData['id'],
                    'approval_url' => $this->getApprovalUrl($orderData),
                    'user_joining_fee_id' => $userJoiningFee->id
                ];
            }

            $error = 'Failed to create PayPal order: ' . $response->body();
            Log::error($error);
            
            return [
                'success' => false,
                'message' => $error
            ];
            
        } catch (\Exception $e) {
            Log::error('PayPal create order error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Capture PayPal payment
     */
   /**
 * Capture PayPal payment - cURL alternative
 */
public function capturePayment($orderId)
{
    try {
        $accessToken = $this->getAccessToken();
        
        $url = $this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture';
        
        Log::info('Capturing payment via cURL. URL: ' . $url);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');  // Empty JSON body
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen('{}')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        Log::debug('cURL Response Code: ' . $httpCode);
        Log::debug('cURL Response Body: ' . $response);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $captureData = json_decode($response, true);
            
            // Update the user_joining_fees record
            $userJoiningFee = UserJoiningFees::where('transaction_id', $orderId)->first();
            
            if ($userJoiningFee) {
                $userJoiningFee->update([
                    'status' => 'completed',
                    'transaction_id' => $captureData['id']
                ]);
            }
            
            return [
                'success' => true,
                'capture_data' => $captureData,
                'user_joining_fee' => $userJoiningFee
            ];
        }
        
        // Update status to failed
        UserJoiningFees::where('transaction_id', $orderId)
            ->update(['status' => 'failed']);
            
        return [
            'success' => false,
            'message' => 'Payment capture failed. HTTP Code: ' . $httpCode . ', Response: ' . $response
        ];
        
    } catch (\Exception $e) {
        Log::error('PayPal capture payment error: ' . $e->getMessage());
        
        UserJoiningFees::where('transaction_id', $orderId)
            ->update(['status' => 'failed']);
            
        return [
            'success' => false,
            'message' => 'Payment capture error: ' . $e->getMessage()
        ];
    }
}

    /**
     * Get payment status
     */
    public function getPaymentStatus($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get($this->baseUrl . '/v2/checkout/orders/' . $orderId);

            if ($response->successful()) {
                $orderData = $response->json();
                
                // Get local record
                $userJoiningFee = UserJoiningFees::where('transaction_id', $orderId)->first();
                
                return [
                    'success' => true,
                    'status' => $orderData['status'],
                    'payment_status' => $userJoiningFee ? $userJoiningFee->status : 'unknown',
                    'order_data' => $orderData,
                    'user_joining_fee' => $userJoiningFee
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get payment status: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('PayPal status check error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Status check error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment($captureId, $amount = null)
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $refundData = [];
            if ($amount !== null) {
                $refundData = [
                    'amount' => [
                        'currency_code' => $this->currency,
                        'value' => number_format($amount, 2, '.', '')
                    ]
                ];
            }
            
            $response = Http::withToken($accessToken)
                ->post($this->baseUrl . '/v2/payments/captures/' . $captureId . '/refund', $refundData);
                
            if ($response->successful()) {
                // Update local record
                UserJoiningFees::where('transaction_id', $captureId)
                    ->update(['status' => 'refunded']);
                    
                return [
                    'success' => true,
                    'refund_data' => $response->json()
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Refund failed: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('PayPal refund error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Refund error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract approval URL from order response
     */
    private function getApprovalUrl($orderData)
    {
        foreach ($orderData['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
}