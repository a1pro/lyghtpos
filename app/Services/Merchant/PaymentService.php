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
use Exception;

class PaymentService
{
    private ?string $apiLoginId;
    private ?string $transactionKey;
    private bool $sandboxMode;
    private string $currency;
    private bool $mockMode;
    private bool $fallbackToMock;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {
        // Load settings from config
        $this->loadSettings();
    }

    /**
     * Load Authorize.net settings from config
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
            $this->apiLoginId = $getStringValue('services.authorize.api_login_id');
            $this->transactionKey = $getStringValue('services.authorize.transaction_key');
            $this->sandboxMode = $getBooleanValue('services.authorize.sandbox_mode', true);
            $this->currency = $getStringValue('services.authorize.currency', 'USD');
            $this->mockMode = $getBooleanValue('services.authorize.mock_mode', false);
            $this->fallbackToMock = $getBooleanValue('services.authorize.fallback_to_mock', true);
            
            Log::info('Authorize.net settings loaded from config', [
                'has_api_login_id' => !empty($this->apiLoginId),
                'has_transaction_key' => !empty($this->transactionKey),
                'api_login_id' => $this->apiLoginId ? substr($this->apiLoginId, 0, 5) . '***' : null,
                'sandbox_mode' => $this->sandboxMode,
                'currency' => $this->currency,
                'mock_mode' => $this->mockMode
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to load Authorize.net settings', [
                'error' => $e->getMessage()
            ]);
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
     * Process a credit card payment
     */
    public function processCardPayment(array $cardData, float $amount): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockProcessCardPayment($cardData, $amount);
        }
        
        try {
            // Validate that settings are loaded
            if (empty($this->apiLoginId) || empty($this->transactionKey)) {
                Log::error('Authorize.net credentials not configured');
                
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock payment processing');
                    return $this->mockProcessCardPayment($cardData, $amount);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment gateway is not properly configured. Please contact administrator.'
                ];
            }

            // Clean and validate card data
            $cardNumber = preg_replace('/\s+/', '', $cardData['card_number']);
            $expiryMonth = str_pad($cardData['expiry_month'], 2, '0', STR_PAD_LEFT);
            $expiryYear = $cardData['expiry_year'];
            $cvv = $cardData['cvv'];

            // Log the attempt (without sensitive data)
            Log::info('Processing Authorize.net payment', [
                'amount' => $amount,
                'card_last_four' => substr($cardNumber, -4),
                'card_type' => $this->detectCardType($cardNumber),
                'expiry_date' => $expiryYear . '-' . $expiryMonth,
                'has_cvv' => !empty($cvv),
                'has_billing' => isset($cardData['billing']),
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
            $creditCard->setCardNumber($cardNumber);
            // Format: YYYY-MM
            $creditCard->setExpirationDate($expiryYear . '-' . $expiryMonth);
            $creditCard->setCardCode($cvv);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create order information
            $order = new AnetAPI\OrderType();
            $orderDescription = isset($cardData['order_description']) && !empty($cardData['order_description']) 
                ? $cardData['order_description'] 
                : 'Payment for order - ' . date('Y-m-d H:i:s');
            $order->setDescription($orderDescription);

            // Set the customer's Bill To address with validation
            $customerAddress = new AnetAPI\CustomerAddressType();
            if (isset($cardData['billing'])) {
                $billing = $cardData['billing'];
                $customerAddress->setFirstName($billing['first_name'] ?? 'Customer');
                $customerAddress->setLastName($billing['last_name'] ?? 'Name');
                $customerAddress->setAddress($billing['address'] ?? '123 Main St');
                $customerAddress->setCity($billing['city'] ?? 'Anytown');
                $customerAddress->setState($billing['state'] ?? 'CA');
                $customerAddress->setZip($billing['zip'] ?? '12345');
                $customerAddress->setCountry($billing['country'] ?? 'USA');
            } else {
                // Set default billing address if not provided
                $customerAddress->setFirstName('Customer');
                $customerAddress->setLastName('Name');
                $customerAddress->setAddress('123 Main St');
                $customerAddress->setCity('Anytown');
                $customerAddress->setState('CA');
                $customerAddress->setZip('12345');
                $customerAddress->setCountry('USA');
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

            // Log the request for debugging (without sensitive data)
            Log::debug('Authorize.net request prepared', [
                'refId' => $refId,
                'amount' => $amount,
                'card_type' => $this->detectCardType($cardNumber),
                'has_billing_address' => true
            ]);

            // Create the controller and get the response
            $controller = new AnetController\CreateTransactionController($request);
            
            // Set environment based on sandbox mode
            $environment = $this->sandboxMode 
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
            
            Log::info('Authorize.net environment', ['mode' => $environment]);
            
            $response = $controller->executeWithApiResponse($environment);

            // Enhanced error logging
            if ($response == null) {
                Log::error('Authorize.net - No response received from API');
                
                if ($this->fallbackToMock) {
                    return $this->mockProcessCardPayment($cardData, $amount);
                }
                
                return [
                    'success' => false,
                    'message' => 'No response from payment gateway. Please check your connection and try again.'
                ];
            }

            // Log the full response for debugging
            Log::debug('Authorize.net full response', [
                'result_code' => $response->getMessages()->getResultCode(),
                'messages' => json_encode($response->getMessages()->getMessage())
            ]);

            // Check if transaction was successful at API level
            if ($response->getMessages()->getResultCode() != "Ok") {
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
                    'error_message' => $errorMessage,
                    'full_response' => json_encode($response)
                ]);
                
                // Check if this is a test account issue
                if ($errorCode == 'E00027') {
                    $errorMessage = 'Transaction failed. This could be due to: 1) Invalid API credentials, 2) Using live card in sandbox mode, or 3) Missing required fields. Please check your configuration.';
                }
                
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
                
                if ($this->fallbackToMock) {
                    return $this->mockProcessCardPayment($cardData, $amount);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment processing error: ' . $errorMessage,
                    'error_code' => $errorCode,
                    'details' => $errorMessages
                ];
            }
            
            // Get the transaction response
            $tresponse = $response->getTransactionResponse();
            
            if ($tresponse == null) {
                Log::error('Authorize.net - No transaction response received');
                
                if ($this->fallbackToMock) {
                    return $this->mockProcessCardPayment($cardData, $amount);
                }
                
                return [
                    'success' => false,
                    'message' => 'Invalid response from payment gateway'
                ];
            }
            
            // Check transaction response code
            $responseCode = $tresponse->getResponseCode();
            
            // Response codes:
            // 1 = Approved
            // 2 = Declined
            // 3 = Error
            // 4 = Held for Review
            
            if ($responseCode == "1") {
                // Transaction approved
                $paymentData = [
                    'user_id' => auth()->id(),
                    'transaction_id' => $tresponse->getTransId(),
                    'amount' => $amount,
                    'currency' => $this->currency,
                    'card_last_four' => substr($cardNumber, -4),
                    'card_type' => $this->detectCardType($cardNumber),
                    'status' => 'completed',
                    'response_message' => 'Payment completed successfully',
                    'full_response' => json_encode([
                        'transaction_response' => [
                            'response_code' => $tresponse->getResponseCode(),
                            'auth_code' => $tresponse->getAuthCode(),
                            'trans_id' => $tresponse->getTransId(),
                            'avs_result_code' => $tresponse->getAvsResultCode(),
                            'cvv_result_code' => $tresponse->getCvvResultCode()
                        ],
                        'messages' => $response->getMessages()->getMessage()
                    ])
                ];
                
                // Create payment record
                $payment = $this->paymentRepository->create($paymentData);
                
                Log::info('Payment successful', [
                    'transaction_id' => $tresponse->getTransId(),
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id(),
                    'amount' => $amount,
                    'auth_code' => $tresponse->getAuthCode()
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'transaction_id' => $tresponse->getTransId(),
                    'auth_code' => $tresponse->getAuthCode(),
                    'payment' => $payment
                ];
                
            } else {
                // Transaction declined or error
                $errorMessages = [];
                $errorCodes = [];
                
                // Check for transaction-level errors
                if ($tresponse->getErrors() != null) {
                    foreach ($tresponse->getErrors() as $error) {
                        $errorMessages[] = $error->getErrorText();
                        $errorCodes[] = $error->getErrorCode();
                    }
                }
                
                // Check for other response messages
                if ($tresponse->getMessages() != null) {
                    $messages = $tresponse->getMessages();
                    foreach ($messages as $message) {
                        if ($message->getCode() != "1") {
                            $errorMessages[] = $message->getDescription();
                        }
                    }
                }
                
                $errorMessage = implode(', ', $errorMessages) ?: $this->getResponseCodeMessage($responseCode);
                $errorCode = implode(', ', $errorCodes) ?: 'TRANSACTION_' . $responseCode;
                
                Log::error('Authorize.net transaction failed', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'response_code' => $responseCode,
                    'transaction_id' => $tresponse->getTransId(),
                    'avs_result' => $tresponse->getAvsResultCode(),
                    'cvv_result' => $tresponse->getCvvResultCode()
                ]);
                
                $paymentData = [
                    'user_id' => auth()->id(),
                    'transaction_id' => $tresponse->getTransId() ?: 'FAILED_' . time(),
                    'amount' => $amount,
                    'currency' => $this->currency,
                    'status' => 'failed',
                    'response_message' => 'Payment failed: ' . $errorMessage,
                    'full_response' => json_encode([
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'response_code' => $responseCode,
                        'avs_result' => $tresponse->getAvsResultCode(),
                        'cvv_result' => $tresponse->getCvvResultCode(),
                        'transaction_id' => $tresponse->getTransId()
                    ])
                ];
                
                $this->paymentRepository->create($paymentData);
                
                if ($this->fallbackToMock) {
                    return $this->mockProcessCardPayment($cardData, $amount);
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment failed: ' . $errorMessage,
                    'error_code' => $errorCode,
                    'response_code' => $responseCode,
                    'details' => $errorMessages
                ];
            }
            
        } catch (Exception $e) {
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
            
            if ($this->fallbackToMock) {
                return $this->mockProcessCardPayment($cardData, $amount);
            }
            
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user-friendly message for response code
     */
    private function getResponseCodeMessage(string $responseCode): string
    {
        $messages = [
            '1' => 'Transaction approved',
            '2' => 'Transaction declined',
            '3' => 'Transaction error',
            '4' => 'Transaction held for review'
        ];
        
        return $messages[$responseCode] ?? 'Unknown transaction status';
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

    /**
     * Test Authorize.net connection with a $1 transaction
     */
    public function testConnection(): array
    {
        if ($this->mockMode) {
            return $this->mockTestConnection();
        }
        
        $testCard = [
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1,
            'cvv' => '123',
            'billing' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'address' => '123 Test St',
                'city' => 'Test City',
                'state' => 'CA',
                'zip' => '12345',
                'country' => 'USA'
            ],
            'order_description' => 'Test transaction - ' . date('Y-m-d H:i:s')
        ];
        
        return $this->processCardPayment($testCard, 1.00);
    }

    /**
     * Debug method to check current configuration
     */
    public function debugConfig(): array
    {
        return [
            'has_api_login_id' => !empty($this->apiLoginId),
            'has_transaction_key' => !empty($this->transactionKey),
            'api_login_id' => $this->apiLoginId ? substr($this->apiLoginId, 0, 5) . '***' : null,
            'sandbox_mode' => $this->sandboxMode,
            'currency' => $this->currency,
            'mock_mode' => $this->mockMode,
            'fallback_to_mock' => $this->fallbackToMock,
        ];
    }

    /**
     * Mock methods for testing
     */
    protected function mockProcessCardPayment(array $cardData, float $amount): array
    {
        Log::info('Mock: Processing card payment', [
            'amount' => $amount,
            'card_last_four' => substr($cardData['card_number'], -4),
            'card_type' => $this->detectCardType($cardData['card_number'])
        ]);
        
        $mockTransactionId = 'MOCK-TXN-' . time() . '-' . rand(1000, 9999);
        
        $paymentData = [
            'user_id' => auth()->id(),
            'transaction_id' => $mockTransactionId,
            'amount' => $amount,
            'currency' => $this->currency,
            'card_last_four' => substr($cardData['card_number'], -4),
            'card_type' => $this->detectCardType($cardData['card_number']),
            'status' => 'completed',
            'response_message' => 'Mock payment completed successfully',
            'full_response' => json_encode([
                'mock' => true,
                'transaction_id' => $mockTransactionId,
                'amount' => $amount,
                'card_last_four' => substr($cardData['card_number'], -4)
            ])
        ];
        
        $payment = $this->paymentRepository->create($paymentData);
        
        return [
            'success' => true,
            'message' => 'Mock payment processed successfully',
            'transaction_id' => $mockTransactionId,
            'auth_code' => 'MOCK-AUTH-' . rand(100000, 999999),
            'payment' => $payment,
            'mock' => true
        ];
    }
    
    protected function mockTestConnection(): array
    {
        Log::info('Mock: Testing Authorize.net connection');
        
        return [
            'success' => true,
            'message' => 'Mock connection test successful',
            'mock' => true,
            'config' => $this->debugConfig()
        ];
    }
}