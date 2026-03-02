<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cyclos\CyclosVoucherService;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    protected $voucherService;

    public function __construct(CyclosVoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function create()
    {
        return view('merchant.pages.vouchers.create');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        try {
            $user = $request->user();
            
            if (empty($user->cyclos_username)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Your account is not linked to Cyclos. Please contact administrator.');
            }

            $result = $this->voucherService->generateVouchers([
                'count' => $request->count,
                'amount' => $request->amount,
                'user' => $user->cyclos_username
            ]);

            $voucherCount = is_array($result) ? count($result) : 0;
            
            return redirect()->route('merchant.vouchers.index')
                ->with('success', "Successfully generated {$voucherCount} voucher(s) in Cyclos.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to generate vouchers: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (empty($user->cyclos_username)) {
                return view('merchant.pages.vouchers.index', [
                    'vouchers' => [],
                    'error' => 'Your account is not linked to Cyclos.'
                ]);
            }

            Log::info('Fetching vouchers for merchant', [
                'cyclos_username' => $user->cyclos_username,
                'user_name' => $user->name,
                'user_email' => $user->email
            ]);

            // Get all vouchers
            $response = $this->voucherService->searchVouchers(['pageSize' => 100]);
            
            // Extract vouchers from response
            $allVouchers = [];
            if (is_array($response)) {
                $allVouchers = $response;
            }

            // Define possible matching names
            $possibleMatches = [
                strtolower($user->cyclos_username),
                strtolower($user->name ?? ''),
                'cyclos merchant',
                'cyclosmerchant',
                'merchant',
            ];

            // Remove empty values
            $possibleMatches = array_filter($possibleMatches);

            // Filter vouchers
            $filteredVouchers = [];
            
            foreach ($allVouchers as $voucher) {
                $ownerDisplay = null;
                
                // Extract owner display name
                if (isset($voucher['owner']) && is_array($voucher['owner'])) {
                    $ownerDisplay = strtolower($voucher['owner']['display'] ?? '');
                }
                
                // Also check createdBy
                if (isset($voucher['createdBy']) && is_array($voucher['createdBy'])) {
                    $createdByDisplay = strtolower($voucher['createdBy']['display'] ?? '');
                    if ($createdByDisplay && !$ownerDisplay) {
                        $ownerDisplay = $createdByDisplay;
                    }
                }

                // Check if this voucher belongs to the merchant
                if ($ownerDisplay) {
                    foreach ($possibleMatches as $match) {
                        if ($ownerDisplay === $match || 
                            strpos($ownerDisplay, $match) !== false || 
                            strpos($match, $ownerDisplay) !== false) {
                            $filteredVouchers[] = $voucher;
                            break;
                        }
                    }
                }
            }

            return view('merchant.pages.vouchers.index', [
                'vouchers' => $filteredVouchers,
                'error' => null,
                'cyclos_username' => $user->cyclos_username,
                'debug_count' => count($allVouchers)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch vouchers', [
                'error' => $e->getMessage()
            ]);

            return view('merchant.pages.vouchers.index', [
                'vouchers' => [],
                'error' => 'Failed to fetch vouchers: ' . $e->getMessage(),
                'cyclos_username' => $request->user()->cyclos_username ?? 'unknown'
            ]);
        }
    }

    /**
     * Change voucher expiration date
     */
    public function changeExpiration(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();
            
            if (empty($user->cyclos_username)) {
                return response()->json(['success' => false, 'error' => 'Not authenticated with Cyclos'], 403);
            }

            $request->validate([
                'expirationDate' => 'required|date'
            ]);

            $result = $this->voucherService->changeExpiration($voucherId, $request->expirationDate);
            
            return response()->json([
                'success' => true,
                'message' => 'Voucher expiration date updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel voucher
     */
    public function cancel(Request $request, string $voucherId)
    {
        try {
            $user = $request->user();
            
            if (empty($user->cyclos_username)) {
                return response()->json(['success' => false, 'error' => 'Not authenticated with Cyclos'], 403);
            }

            $result = $this->voucherService->cancelVoucher($voucherId);
            
            return response()->json([
                'success' => true,
                'message' => 'Voucher cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}