<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Merchant ID 38
        $merchantId = 38;
        $merchantCyclosId = '7157739016485331116';
        
        // Customer IDs from your system (these are example Cyclos IDs from your logs)
        $customers = [
            ['id' => 1, 'cyclos_id' => '7157739016487166124', 'name' => 'John Doe'],
            ['id' => 2, 'cyclos_id' => '7157739016487166125', 'name' => 'Jane Smith'],
            ['id' => 3, 'cyclos_id' => '7157739016487166126', 'name' => 'Bob Johnson'],
            ['id' => 4, 'cyclos_id' => '7157739016487166127', 'name' => 'Alice Brown'],
            ['id' => 5, 'cyclos_id' => '7157739016487166128', 'name' => 'Charlie Wilson'],
        ];
        
        // Sample cart items for different transactions
        $cartItems = [
            [
                [
                    'name' => 'Product 1',
                    'price' => 100.00,
                    'quantity' => 2,
                    'product_id' => '7157739016486903980',
                    'cyclos_product_id' => '7157739016486903980'
                ],
                [
                    'name' => 'Product 2',
                    'price' => 50.00,
                    'quantity' => 1,
                    'product_id' => '7157739016486903981',
                    'cyclos_product_id' => '7157739016486903981'
                ]
            ],
            [
                [
                    'name' => 'Product 3',
                    'price' => 75.00,
                    'quantity' => 3,
                    'product_id' => '7157739016486903982',
                    'cyclos_product_id' => '7157739016486903982'
                ]
            ],
            [
                [
                    'name' => 'Product 4',
                    'price' => 200.00,
                    'quantity' => 1,
                    'product_id' => '7157739016486903983',
                    'cyclos_product_id' => '7157739016486903983'
                ],
                [
                    'name' => 'Product 5',
                    'price' => 150.00,
                    'quantity' => 2,
                    'product_id' => '7157739016486903984',
                    'cyclos_product_id' => '7157739016486903984'
                ]
            ],
            [
                [
                    'name' => 'Product 6',
                    'price' => 25.00,
                    'quantity' => 5,
                    'product_id' => '7157739016486903985',
                    'cyclos_product_id' => '7157739016486903985'
                ],
                [
                    'name' => 'Product 7',
                    'price' => 30.00,
                    'quantity' => 2,
                    'product_id' => '7157739016486903986',
                    'cyclos_product_id' => '7157739016486903986'
                ]
            ],
            [
                [
                    'name' => 'Product 8',
                    'price' => 500.00,
                    'quantity' => 1,
                    'product_id' => '7157739016486903987',
                    'cyclos_product_id' => '7157739016486903987'
                ]
            ]
        ];
        
        // Different payment methods
        $paymentMethods = ['card', 'cash', 'unyti', 'card', 'cash'];
        $paymentStatuses = ['completed', 'completed', 'pending', 'completed', 'completed'];
        
        // Transaction data with different amounts
        $transactions = [
            // Transaction 1: $250 total (2 items: 2x100 + 1x50)
            [
                'total_amount' => 250.00,
                'payment_method' => 'card',
                'payment_status' => 'completed',
                'received_amount' => null,
                'payment_response' => [
                    'transaction_id' => '120080144288',
                    'auth_code' => 'V1XEPV',
                    'card_type' => 'visa',
                    'card_last_four' => '4242'
                ]
            ],
            // Transaction 2: $225 total (3x75)
            [
                'total_amount' => 225.00,
                'payment_method' => 'cash',
                'payment_status' => 'completed',
                'received_amount' => 230.00,
                'payment_response' => null
            ],
            // Transaction 3: $500 total (1x200 + 2x150)
            [
                'total_amount' => 500.00,
                'payment_method' => 'unyti',
                'payment_status' => 'pending',
                'received_amount' => null,
                'payment_response' => [
                    'unyti_user_id' => 'unyti_user_12345',
                    'status' => 'initiated'
                ]
            ],
            // Transaction 4: $185 total (5x25 + 2x30)
            [
                'total_amount' => 185.00,
                'payment_method' => 'card',
                'payment_status' => 'completed',
                'received_amount' => null,
                'payment_response' => [
                    'transaction_id' => '120080144289',
                    'auth_code' => 'M2YFQW',
                    'card_type' => 'mastercard',
                    'card_last_four' => '5555'
                ]
            ],
            // Transaction 5: $500 total (1x500)
            [
                'total_amount' => 500.00,
                'payment_method' => 'cash',
                'payment_status' => 'completed',
                'received_amount' => 500.00,
                'payment_response' => null
            ]
        ];
        
        foreach ($transactions as $index => $data) {
            $totalAmount = $data['total_amount'];
            $paymentMethod = $data['payment_method'];
            
            // Calculate fees and splits
            $platformFeePercentage = 3.5;
            $platformFee = ($totalAmount * $platformFeePercentage) / 100;
            $amountAfterPlatformFee = $totalAmount - $platformFee;
            
            // Split after platform fee: Merchant 90%, Admin 10%
            $merchantShare = ($amountAfterPlatformFee * 90) / 100;
            $adminShare = ($amountAfterPlatformFee * 10) / 100;
            
            // Calculate card fee if applicable
            $cardFee = $paymentMethod === 'card' ? ($totalAmount * 0.035) : 0;
            
            // Generate transaction ID based on payment method
            $transactionId = '';
            switch ($paymentMethod) {
                case 'card':
                    $transactionId = 'CARD_' . time() . '_' . ($index + 1);
                    break;
                case 'cash':
                    $transactionId = 'CASH_' . time() . '_' . ($index + 1);
                    break;
                case 'unyti':
                    $transactionId = 'UNYTI_' . time() . '_' . ($index + 1);
                    break;
            }
            
            // Prepare remarks based on payment method
            $remarks = "Payment ID: " . ($index + 1) . "\n";
            $remarks .= "Transaction ID: " . $transactionId . "\n";
            $remarks .= "Platform Fee: " . number_format($platformFee, 2) . "\n";
            $remarks .= "Merchant Share: " . number_format($merchantShare, 2) . "\n";
            $remarks .= "Admin Share: " . number_format($adminShare, 2) . "\n";
            
            if ($paymentMethod === 'card') {
                $remarks .= "Card Fee: " . number_format($cardFee, 2) . "\n";
            }
            
            if ($paymentMethod === 'cash' && isset($data['received_amount'])) {
                $remarks .= "Cash Received: " . number_format($data['received_amount'], 2) . "\n";
                $remarks .= "Change Due: " . number_format($data['received_amount'] - $totalAmount, 2) . "\n";
            }
            
            // Prepare payment response JSON
            $paymentResponse = null;
            if ($data['payment_response']) {
                $paymentResponse = array_merge($data['payment_response'], [
                    'platform_fee' => $platformFee,
                    'merchant_share' => $merchantShare,
                    'admin_share' => $adminShare,
                    'amount_after_platform_fee' => $amountAfterPlatformFee,
                    'processed_at' => Carbon::now()->subDays($index)->toDateTimeString()
                ]);
            }
            
            // Create the transaction
            Transaction::create([
                'transaction_id' => $transactionId,
                'merchant_id' => $merchantId,
                'merchant_cyclos_id' => $merchantCyclosId,
                'customer_id' => $customers[$index]['id'],
                'customer_cyclos_id' => $customers[$index]['cyclos_id'],
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
                'amount_after_platform_fee' => $amountAfterPlatformFee,
                'admin_share' => $adminShare,
                'merchant_share' => $merchantShare,
                'payment_method' => $paymentMethod,
                'payment_status' => $data['payment_status'],
                'cart_data' => $cartItems[$index],
                'payment_response' => $paymentResponse,
                'received_amount' => $data['received_amount'] ?? null,
                'fee_breakdown' => [
                    'platform_fee_percentage' => $platformFeePercentage,
                    'platform_fee_amount' => round($platformFee, 2),
                    'merchant_share_percentage' => 90,
                    'admin_share_percentage' => 10,
                    'calculation_details' => [
                        'formula' => "Total - Platform Fee (3.5%) = Amount After Fee, then split Merchant (90%) / Admin (10%)",
                        'total_amount' => $totalAmount,
                        'platform_fee_deduction' => round($platformFee, 2),
                        'amount_for_split' => round($amountAfterPlatformFee, 2),
                        'merchant_share_amount' => round($merchantShare, 2),
                        'admin_share_amount' => round($adminShare, 2)
                    ],
                    'card_fee' => $paymentMethod === 'card' ? [
                        'percentage' => 3.5,
                        'amount' => $cardFee
                    ] : null
                ],
                'paid_at' => Carbon::now()->subDays($index)->subHours(rand(1, 12))
            ]);
        }
        
        // Add 5 more transactions with varying amounts and dates
        $additionalTransactions = [
            [
                'total_amount' => 75.50,
                'payment_method' => 'card',
                'days_ago' => 2,
                'customer_index' => 0
            ],
            [
                'total_amount' => 1200.00,
                'payment_method' => 'unyti',
                'days_ago' => 5,
                'customer_index' => 1
            ],
            [
                'total_amount' => 45.25,
                'payment_method' => 'cash',
                'days_ago' => 7,
                'customer_index' => 2
            ],
            [
                'total_amount' => 350.00,
                'payment_method' => 'card',
                'days_ago' => 10,
                'customer_index' => 3
            ],
            [
                'total_amount' => 89.99,
                'payment_method' => 'cash',
                'days_ago' => 14,
                'customer_index' => 4
            ]
        ];
        
        foreach ($additionalTransactions as $index => $data) {
            $totalAmount = $data['total_amount'];
            $paymentMethod = $data['payment_method'];
            $daysAgo = $data['days_ago'];
            $customerIndex = $data['customer_index'];
            
            // Calculate fees
            $platformFee = ($totalAmount * 3.5) / 100;
            $amountAfterPlatformFee = $totalAmount - $platformFee;
            $merchantShare = ($amountAfterPlatformFee * 90) / 100;
            $adminShare = ($amountAfterPlatformFee * 10) / 100;
            
            $transactionId = strtoupper($paymentMethod) . '_' . time() . '_' . ($index + 6);
            
            $cartItemsSample = [
                [
                    'name' => 'Sample Product ' . ($index + 1),
                    'price' => $totalAmount,
                    'quantity' => 1,
                    'product_id' => '715773901648690398' . ($index + 8),
                    'cyclos_product_id' => '715773901648690398' . ($index + 8)
                ]
            ];
            
            Transaction::create([
                'transaction_id' => $transactionId,
                'merchant_id' => $merchantId,
                'merchant_cyclos_id' => $merchantCyclosId,
                'customer_id' => $customers[$customerIndex]['id'],
                'customer_cyclos_id' => $customers[$customerIndex]['cyclos_id'],
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
                'amount_after_platform_fee' => $amountAfterPlatformFee,
                'admin_share' => $adminShare,
                'merchant_share' => $merchantShare,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'unyti' ? 'pending' : 'completed',
                'cart_data' => $cartItemsSample,
                'payment_response' => $paymentMethod === 'card' ? [
                    'transaction_id' => 'AUTH_' . rand(100000000, 999999999),
                    'auth_code' => strtoupper(substr(md5(rand()), 0, 6)),
                    'card_type' => 'visa',
                    'card_last_four' => '4242'
                ] : ($paymentMethod === 'unyti' ? [
                    'unyti_user_id' => 'unyti_user_' . rand(10000, 99999),
                    'status' => 'initiated'
                ] : null),
                'received_amount' => $paymentMethod === 'cash' ? $totalAmount : null,
                'fee_breakdown' => [
                    'platform_fee_percentage' => 3.5,
                    'platform_fee_amount' => round($platformFee, 2),
                    'merchant_share_percentage' => 90,
                    'admin_share_percentage' => 10,
                    'calculation_details' => [
                        'total_amount' => $totalAmount,
                        'platform_fee_deduction' => round($platformFee, 2),
                        'amount_for_split' => round($amountAfterPlatformFee, 2),
                        'merchant_share_amount' => round($merchantShare, 2),
                        'admin_share_amount' => round($adminShare, 2)
                    ]
                ],
                'paid_at' => Carbon::now()->subDays($daysAgo)->subHours(rand(1, 23))
            ]);
        }
        
        $this->command->info('Successfully seeded ' . (count($transactions) + count($additionalTransactions)) . ' transactions for merchant ID ' . $merchantId);
    }
}