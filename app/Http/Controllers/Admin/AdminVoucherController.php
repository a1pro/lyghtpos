<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Cyclos\CyclosVoucherService;
use Illuminate\Support\Facades\Log;

class AdminVoucherController extends Controller
{
    protected $voucherService;

    public function __construct(CyclosVoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Show form to create vouchers for merchants
     */
    public function create()
    {
        $merchants = User::where('role_id', 2)
            ->whereNotNull('cyclos_username')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('admin.pages.vouchers.create', compact('merchants'));
    }

    /**
     * Generate vouchers for selected merchant
     */
    public function generate(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:users,id',
            'count' => 'required|integer|min:1|max:100',
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        try {
            $merchant = User::findOrFail($request->merchant_id);
            
            if (empty($merchant->cyclos_username)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected merchant does not have a Cyclos account.');
            }

            Log::info('Admin generating vouchers for merchant', [
                'admin_id' => $request->user()->id,
                'merchant_id' => $merchant->id,
                'merchant_username' => $merchant->cyclos_username,
                'count' => $request->count,
                'amount' => $request->amount
            ]);

            $result = $this->voucherService->generateVouchers([
                'count' => $request->count,
                'amount' => $request->amount,
                'user' => $merchant->cyclos_username
            ]);

            $voucherCount = is_array($result) ? count($result) : 0;
            
            return redirect()->route('admin.vouchers.index')
                ->with('success', "Successfully generated {$voucherCount} voucher(s) for merchant {$merchant->first_name} {$merchant->last_name}.");

        } catch (\Exception $e) {
            Log::error('Admin voucher generation failed', [
                'error' => $e->getMessage(),
                'merchant_id' => $request->merchant_id
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to generate vouchers: ' . $e->getMessage());
        }
    }

    /**
     * List all vouchers (admin view) with filters
     */
    public function index(Request $request)
    {
        // Initialize default values
        $vouchers = [];
        $merchants = [];
        $selectedMerchantId = $request->get('merchant_id');
        $selectedStatus = $request->get('status');
        $total_vouchers = 0;
        $error = null;

        try {
            $user = $request->user();

            // Verify user is admin (role_id = 1)
            if ($user->role_id != 1) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access.');
            }

            // Get all merchants for filter dropdown
            $merchants = User::where('role_id', 2)
                ->whereNotNull('cyclos_username')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            Log::info('Admin fetching all vouchers', [
                'admin_id' => $user->id,
                'admin_name' => $user->first_name . ' ' . $user->last_name
            ]);

            // Get all vouchers from Cyclos
            $response = $this->voucherService->searchVouchers(['pageSize' => 200]);
            
            // Extract vouchers from response
            $allVouchers = [];
            if (is_array($response)) {
                $allVouchers = $response;
            } elseif (isset($response['result'])) {
                $allVouchers = $response['result'];
            }

            // For admin, identify which merchant owns each voucher and apply filters
            $vouchersWithMerchantInfo = [];
            
            foreach ($allVouchers as $voucher) {
                $ownerDisplay = $this->extractOwnerDisplay($voucher);
                $voucherWithInfo = $voucher;
                
                if ($ownerDisplay) {
                    // Find which merchant this voucher belongs to
                    foreach ($merchants as $merchant) {
                        $cyclosUsername = strtolower($merchant->cyclos_username);
                        $normalizedOwner = strtolower($ownerDisplay);
                        
                        if ($normalizedOwner === $cyclosUsername || 
                            strpos($normalizedOwner, $cyclosUsername) !== false || 
                            strpos($cyclosUsername, $normalizedOwner) !== false) {
                            
                            // Add merchant info to voucher
                            $voucherWithInfo['merchant_info'] = [
                                'id' => $merchant->id,
                                'first_name' => $merchant->first_name,
                                'last_name' => $merchant->last_name,
                                'full_name' => $merchant->first_name . ' ' . $merchant->last_name,
                                'email' => $merchant->email,
                                'cyclos_username' => $merchant->cyclos_username
                            ];
                            break;
                        }
                    }
                }
                
                $vouchersWithMerchantInfo[] = $voucherWithInfo;
            }

            // Apply filters
            $filteredVouchers = $vouchersWithMerchantInfo;
            
            // Filter by merchant
            if ($selectedMerchantId) {
                $filteredVouchers = array_filter($filteredVouchers, function($voucher) use ($selectedMerchantId) {
                    return isset($voucher['merchant_info']) && $voucher['merchant_info']['id'] == $selectedMerchantId;
                });
            }
            
            // Filter by status
            if ($selectedStatus) {
                $filteredVouchers = array_filter($filteredVouchers, function($voucher) use ($selectedStatus) {
                    $status = $voucher['status'] ?? 'unknown';
                    return strtoupper($status) === strtoupper($selectedStatus);
                });
            }

            $vouchers = array_values($filteredVouchers);
            $total_vouchers = count($allVouchers);

        } catch (\Exception $e) {
            Log::error('Failed to fetch vouchers for admin', [
                'error' => $e->getMessage()
            ]);
            $error = 'Failed to fetch vouchers: ' . $e->getMessage();
        }

        $statusCounts = $this->getStatusCounts($vouchersWithMerchantInfo ?? []);

        return view('admin.pages.vouchers.index', [
            'vouchers' => $vouchers,
            'merchants' => $merchants,
            'selectedMerchantId' => $selectedMerchantId,
            'selectedStatus' => $selectedStatus,
            'total_vouchers' => $total_vouchers,
            'statusCounts' => $statusCounts,
            'error' => $error
        ]);
    }

    /**
     * View single voucher details
     */
    public function show(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();

            if ($user->role_id != 1) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access.');
            }

            $voucher = $this->voucherService->getVoucher($voucherId);

            if (!$voucher) {
                return redirect()->route('admin.vouchers.index')
                    ->with('error', 'Voucher not found.');
            }

            $ownerDisplay = $this->extractOwnerDisplay($voucher);
            if ($ownerDisplay) {
                $merchant = User::where('role_id', 2)
                    ->where('cyclos_username', 'LIKE', '%' . $ownerDisplay . '%')
                    ->first();
                
                if ($merchant) {
                    $voucher['merchant_info'] = [
                        'id' => $merchant->id,
                        'first_name' => $merchant->first_name,
                        'last_name' => $merchant->last_name,
                        'full_name' => $merchant->first_name . ' ' . $merchant->last_name,
                        'email' => $merchant->email,
                        'cyclos_username' => $merchant->cyclos_username
                    ];
                }
            }

            return view('admin.pages.vouchers.show', compact('voucher'));

        } catch (\Exception $e) {
            Log::error('Failed to fetch voucher details', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.vouchers.index')
                ->with('error', 'Failed to fetch voucher details: ' . $e->getMessage());
        }
    }

    /**
     * Show form to change expiration date
     */
    public function editExpiration(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();

            if ($user->role_id != 1) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access.');
            }

            $voucher = $this->voucherService->getVoucher($voucherId);

            if (!$voucher) {
                return redirect()->route('admin.vouchers.index')
                    ->with('error', 'Voucher not found.');
            }

            $ownerDisplay = $this->extractOwnerDisplay($voucher);
            if ($ownerDisplay) {
                $merchant = User::where('role_id', 2)
                    ->where('cyclos_username', 'LIKE', '%' . $ownerDisplay . '%')
                    ->first();
                
                if ($merchant) {
                    $voucher['merchant_info'] = [
                        'id' => $merchant->id,
                        'first_name' => $merchant->first_name,
                        'last_name' => $merchant->last_name,
                        'full_name' => $merchant->first_name . ' ' . $merchant->last_name,
                        'email' => $merchant->email,
                        'cyclos_username' => $merchant->cyclos_username
                    ];
                }
            }

            return view('admin.pages.vouchers.change-expiration', compact('voucher'));

        } catch (\Exception $e) {
            Log::error('Failed to load expiration form', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.vouchers.index')
                ->with('error', 'Failed to load expiration form: ' . $e->getMessage());
        }
    }

    /**
     * Change voucher expiration date
     */
    public function changeExpiration(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();

            if ($user->role_id != 1) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'Unauthorized. Only admins can change voucher expiration.'
                    ], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized access.');
            }

            $request->validate([
                'expirationDate' => 'required|date|after:today',
                'comments' => 'nullable|string|max:500'
            ]);

            Log::info('Admin changing voucher expiration', [
                'admin_id' => $request->user()->id,
                'admin_name' => $user->first_name . ' ' . $user->last_name,
                'voucher_id' => $voucherId,
                'expiration_date' => $request->expirationDate,
                'has_comments' => $request->has('comments')
            ]);

            $result = $this->voucherService->changeExpiration(
                $voucherId, 
                $request->expirationDate,
                $request->comments
            );
            
            if ($request->boolean('send_notification')) {
                $this->sendExpirationNotification($voucherId, $request->expirationDate, $request->comments);
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Voucher expiration date updated successfully',
                    'data' => $result['data'] ?? null
                ]);
            }

            return redirect()->route('admin.vouchers.show', $voucherId)
                ->with('success', 'Voucher expiration date updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'error' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            Log::error('Failed to change voucher expiration', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to change expiration: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancel voucher
     */
    public function cancel(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();

            if ($user->role_id != 1) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'Unauthorized. Only admins can cancel vouchers.'
                    ], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized access.');
            }

            $request->validate([
                'comments' => 'nullable|string|max:500'
            ]);

            Log::info('Admin cancelling voucher', [
                'admin_id' => $user->id,
                'admin_name' => $user->first_name . ' ' . $user->last_name,
                'voucher_id' => $voucherId,
                'has_comments' => $request->has('comments')
            ]);

            $result = $this->voucherService->cancelVoucher($voucherId, $request->comments);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Voucher cancelled successfully',
                    'data' => $result['data'] ?? null
                ]);
            }

            return redirect()->route('admin.vouchers.index')
                ->with('success', 'Voucher cancelled successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to cancel voucher', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to cancel voucher: ' . $e->getMessage());
        }
    }

    /**
     * Show cancel confirmation page
     */
    public function confirmCancel(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();

            if ($user->role_id != 1) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access.');
            }

            $voucher = $this->voucherService->getVoucher($voucherId);

            if (!$voucher) {
                return redirect()->route('admin.vouchers.index')
                    ->with('error', 'Voucher not found.');
            }

            $ownerDisplay = $this->extractOwnerDisplay($voucher);
            if ($ownerDisplay) {
                $merchant = User::where('role_id', 2)
                    ->where('cyclos_username', 'LIKE', '%' . $ownerDisplay . '%')
                    ->first();
                
                if ($merchant) {
                    $voucher['merchant_info'] = [
                        'id' => $merchant->id,
                        'first_name' => $merchant->first_name,
                        'last_name' => $merchant->last_name,
                        'full_name' => $merchant->first_name . ' ' . $merchant->last_name,
                        'email' => $merchant->email,
                        'cyclos_username' => $merchant->cyclos_username
                    ];
                }
            }

            return view('admin.pages.vouchers.cancel', compact('voucher'));

        } catch (\Exception $e) {
            Log::error('Failed to load cancel form', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.vouchers.index')
                ->with('error', 'Failed to load cancel form: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to merchant about expiration change
     */
    private function sendExpirationNotification(string $voucherId, string $newDate, ?string $comments = null): void
    {
        try {
            $voucher = $this->voucherService->getVoucher($voucherId);
            $ownerDisplay = $this->extractOwnerDisplay($voucher);
            
            if ($ownerDisplay) {
                $merchant = User::where('role_id', 2)
                    ->where('cyclos_username', 'LIKE', '%' . $ownerDisplay . '%')
                    ->first();

                if ($merchant && $merchant->email) {
                    Log::info('Expiration change notification would be sent', [
                        'merchant_email' => $merchant->email,
                        'voucher_id' => $voucherId,
                        'new_date' => $newDate
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send expiration notification', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get status counts for filter badges
     */
    private function getStatusCounts($vouchers)
    {
        $counts = [
            'ACTIVE' => 0,
            'OPEN' => 0,
            'REDEEMED' => 0,
            'EXPIRED' => 0,
            'CANCELED' => 0,
            'SCHEDULED' => 0,
            'PENDING' => 0,
            'TOTAL' => count($vouchers)
        ];

        foreach ($vouchers as $voucher) {
            $status = $voucher['status'] ?? 'unknown';
            $status = strtoupper($status);
            
            if (isset($counts[$status])) {
                $counts[$status]++;
            } elseif ($status === 'ACTIVE' || $status === 'OPEN') {
                $counts['ACTIVE']++;
            }
        }

        return $counts;
    }

    /**
     * Extract owner display name from voucher
     */
    private function extractOwnerDisplay($voucher)
    {
        if (isset($voucher['owner']) && is_array($voucher['owner'])) {
            return $voucher['owner']['display'] ?? $voucher['owner']['name'] ?? null;
        }
        
        if (isset($voucher['createdBy']) && is_array($voucher['createdBy'])) {
            return $voucher['createdBy']['display'] ?? $voucher['createdBy']['name'] ?? null;
        }
        
        if (isset($voucher['user']) && is_array($voucher['user'])) {
            return $voucher['user']['display'] ?? $voucher['user']['name'] ?? null;
        }
        
        return null;
    }


     
}