<?php

namespace App\Services\Merchant;

use App\Models\MerchantPayment;
use App\Repositories\Merchant\Interfaces\PaymentRepositoryInterface;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\Representation;
use Illuminate\Support\Facades\DB;
use App\Models\AppSetting;

class PaymentService
{
    private $apiLoginId;
    private $transactionKey;
    private $sandboxMode;
    private $currency;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {
        // Load settings from database
        $this->loadSettings();
    }

    /**
     * Load Authorize.net settings from database
     */
    private function loadSettings(): void
    {
        try {
            // Get API Login ID from database
            $apiLoginIdSetting = AppSetting::where('group', 'authorize')
                ->where('key', 'AUTHORIZE_API_LOGIN_ID')
                ->first();
            
            $this->apiLoginId = $apiLoginIdSetting ? $apiLoginIdSetting->value : null;
            
            // Get Transaction Key from database
            $transactionKeySetting = AppSetting::where('group', 'authorize')
                ->where('key', 'AUTHORIZE_TRANSACTION_KEY')
                ->first();
            
            $this->transactionKey = $transactionKeySetting ? $transactionKeySetting->value : null;
            
            // Get mode from config or env (for sandbox/production)
            $this->sandboxMode = config('authorize.sandbox_mode', env('AUTHORIZE_SANDBOX_MODE', true));
            
            // Get currency from config or env
            $this->currency = config('authorize.currency', env('AUTHORIZE_CURRENCY', 'USD'));
            
            Log::info('Authorize.net settings loaded from database', [
                'has_api_login_id' => !empty($this->apiLoginId),
                'has_transaction_key' => !empty($this->transactionKey),
                'sandbox_mode' => $this->sandboxMode,
                'currency' => $this->currency
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load Authorize.net settings from database', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process a credit card payment
     */
    public function processCardPayment(array $cardData, float $amount): array
    {
        try {
            // Validate that settings are loaded
            if (empty($this->apiLoginId) || empty($this->transactionKey)) {
                Log::error('Authorize.net credentials not configured in database');
                return [
                    'success' => false,
                    'message' => 'Payment gateway is not properly configured. Please contact administrator.'
                ];
            }

            // Log the attempt (without sensitive data)
            Log::info('Processing Authorize.net payment', [
                'amount' => $amount,
                'card_last_four' => substr(str_replace(' ', '', $cardData['card_number']), -4),
                'has_api_login_id' => !empty($this->apiLoginId),
                'has_transaction_key' => !empty($this->transactionKey),
                'sandbox_mode' => $this->sandboxMode
            ]);

            // Create a merchantAuthenticationType object with authentication details
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($this->apiLoginId);
            $merchantAuthentication->setTransactionKey($this->transactionKey);

            // Set the transaction's refId
            $refId = 'ref' . time();

            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(str_replace(' ', '', $cardData['card_number']));
            $creditCard->setExpirationDate($cardData['expiry_year'] . '-' . $cardData['expiry_month']);
            $creditCard->setCardCode($cardData['cvv']);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create order information
            $order = new AnetAPI\OrderType();
            if (isset($cardData['order_description']) && !empty($cardData['order_description'])) {
                $order->setDescription($cardData['order_description']);
            }

            // Set the customer's Bill To address
            $customerAddress = new AnetAPI\CustomerAddressType();
            if (isset($cardData['billing'])) {
                $billing = $cardData['billing'];
                $customerAddress->setFirstName($billing['first_name'] ?? '');
                $customerAddress->setLastName($billing['last_name'] ?? '');
                $customerAddress->setAddress($billing['address'] ?? '');
                $customerAddress->setCity($billing['city'] ?? '');
                $customerAddress->setState($billing['state'] ?? '');
                $customerAddress->setZip($billing['zip'] ?? '');
                $customerAddress->setCountry($billing['country'] ?? '');
            }

            // Create a transaction
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setOrder($order);
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setBillTo($customerAddress);

            // Assemble the complete transaction request
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequestType);

            // Create the controller and get the response
            $controller = new AnetController\CreateTransactionController($request);
            
            // Set environment based on sandbox mode
            $environment = $this->sandboxMode 
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
            
            Log::info('Authorize.net environment', ['mode' => $environment]);
            
            $response = $controller->executeWithApiResponse($environment);

            // Log raw response for debugging
            Log::info('Authorize.net raw response', [
                'response' => $response ? json_encode($response) : 'No response'
            ]);

            // Process the response
            if ($response != null) {
                // Check if transaction was successful
                if ($response->getMessages()->getResultCode() == "Ok") {
                    $tresponse = $response->getTransactionResponse();
                    
                    if ($tresponse != null && $tresponse->getResponseCode() == "1") {
                        // Transaction approved
                        $paymentData = [
                            'user_id' => auth()->id(),
                            'transaction_id' => $tresponse->getTransId(),
                            'amount' => $amount,
                            'currency' => $this->currency,
                            'card_last_four' => substr(str_replace(' ', '', $cardData['card_number']), -4),
                            'card_type' => $this->detectCardType($cardData['card_number']),
                            'status' => 'completed',
                            'response_message' => 'Payment completed successfully',
                            'full_response' => json_encode([
                                'transaction_response' => [
                                    'response_code' => $tresponse->getResponseCode(),
                                    'auth_code' => $tresponse->getAuthCode(),
                                    'trans_id' => $tresponse->getTransId()
                                ],
                                'messages' => $response->getMessages()->getMessage()
                            ])
                        ];
                        
                        // Create payment record
                        $payment = $this->paymentRepository->create($paymentData);
                        
                        Log::info('Payment successful', [
                            'transaction_id' => $tresponse->getTransId(),
                            'payment_id' => $payment->id,
                            'user_id' => auth()->id()
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Payment processed successfully',
                            'transaction_id' => $tresponse->getTransId(),
                            'payment' => $payment
                        ];
                        
                    } else {
                        // Transaction failed at processor level
                        $errorMessages = [];
                        $errorCodes = [];
                        
                        if ($tresponse != null && $tresponse->getErrors() != null) {
                            foreach ($tresponse->getErrors() as $error) {
                                $errorMessages[] = $error->getErrorText();
                                $errorCodes[] = $error->getErrorCode();
                            }
                        }
                        
                        $errorMessage = implode(', ', $errorMessages) ?: 'Transaction was declined';
                        $errorCode = implode(', ', $errorCodes) ?: 'UNKNOWN';
                        
                        Log::error('Authorize.net transaction failed', [
                            'error_code' => $errorCode,
                            'error_message' => $errorMessage,
                            'response_code' => $tresponse ? $tresponse->getResponseCode() : 'unknown'
                        ]);
                        
                        $paymentData = [
                            'user_id' => auth()->id(),
                            'transaction_id' => 'FAILED_' . time(),
                            'amount' => $amount,
                            'currency' => $this->currency,
                            'status' => 'failed',
                            'response_message' => 'Payment failed: ' . $errorMessage,
                            'full_response' => json_encode([
                                'error_code' => $errorCode,
                                'error_message' => $errorMessage,
                                'response' => $response
                            ])
                        ];
                        
                        $this->paymentRepository->create($paymentData);
                        
                        return [
                            'success' => false,
                            'message' => 'Payment failed: ' . $errorMessage
                        ];
                    }
                } else {
                    // API-level error
                    $errorMessages = [];
                    $errorCodes = [];
                    
                    $messages = $response->getMessages();
                    if ($messages != null) {
                        foreach ($messages->getMessage() as $message) {
                            $errorMessages[] = $message->getText();
                            $errorCodes[] = $message->getCode();
                        }
                    }
                    
                    $errorMessage = implode(', ', $errorMessages) ?: 'API Error';
                    $errorCode = implode(', ', $errorCodes) ?: 'API_ERROR';
                    
                    Log::error('Authorize.net API error', [
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage
                    ]);
                    
                    $paymentData = [
                        'user_id' => auth()->id(),
                        'transaction_id' => 'API_ERROR_' . time(),
                        'amount' => $amount,
                        'currency' => $this->currency,
                        'status' => 'failed',
                        'response_message' => 'Payment processing error: ' . $errorMessage,
                        'full_response' => json_encode([
                            'error_code' => $errorCode,
                            'error_message' => $errorMessage,
                            'response' => $response
                        ])
                    ];
                    
                    $this->paymentRepository->create($paymentData);
                    
                    return [
                        'success' => false,
                        'message' => 'Payment processing error: ' . $errorMessage
                    ];
                }
            } else {
                // No response from API
                Log::error('Authorize.net no response from API');
                
                $paymentData = [
                    'user_id' => auth()->id(),
                    'transaction_id' => 'NO_RESPONSE_' . time(),
                    'amount' => $amount,
                    'currency' => $this->currency,
                    'status' => 'failed',
                    'response_message' => 'No response from payment gateway',
                    'full_response' => json_encode(['error' => 'No response from API'])
                ];
                
                $this->paymentRepository->create($paymentData);
                
                return [
                    'success' => false,
                    'message' => 'No response from payment gateway. Please try again.'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Authorize.net Payment Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $paymentData = [
                'user_id' => auth()->id(),
                'transaction_id' => 'EXCEPTION_' . time(),
                'amount' => $amount,
                'currency' => $this->currency,
                'status' => 'failed',
                'response_message' => 'Payment processing error: ' . $e->getMessage(),
                'full_response' => json_encode([
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
            ];
            
            $this->paymentRepository->create($paymentData);
            
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all payments
     */
    public function getAllPayments()
    {
        return $this->paymentRepository->all();
    }

    /**
     * Get payment by ID
     */
    public function getPayment(int $id)
    {
        return $this->paymentRepository->find($id);
    }

    /**
     * Detect card type based on first few digits
     */
    private function detectCardType(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'discover' => '/^6(?:011|5)/',
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $type;
            }
        }
        
        return 'unknown';
    }

    /**
     * Reload settings (useful after updating settings)
     */
    public function reloadSettings(): void
    {
        $this->loadSettings();
        Log::info('Authorize.net settings reloaded');
    }

    /**
     * Check if Authorize.net is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiLoginId) && !empty($this->transactionKey);
    }
}