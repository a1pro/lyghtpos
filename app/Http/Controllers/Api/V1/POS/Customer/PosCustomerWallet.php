<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\PosCustomerWalletService;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;
use App\Http\Requests\Api\POS\Customer\POSCustomerWalletRequest;
use Illuminate\Http\Request;
use DB;
use Auth;

class PosCustomerWallet extends Controller
{
    protected $posWalletService;
    protected  $cyclosPayment;

    /**
     * Inject wallet service and payment service dependencies
     */
    public function __construct(PosCustomerWalletService $posWalletService, CyclosPaymentService $cyclosPayment)
    {
        $this->posWalletService = $posWalletService;
        $this->cyclosPayment = $cyclosPayment;
    }

    /**
     * Get wallet balance for authenticated customer
     */
    public function getBalance(Request $request)
    {
        $user = $request->user();

        $cyclosUserId = $user->cyclos_user_id ?? $user->cyclos_id ?? $user->id;

        if (!$cyclosUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cyclos user ID not found for this customer'
            ], 404);
        }

        $balance = $this->posWalletService->getCustomerWalletBalance($cyclosUserId);

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'currency_symbol' => app(CyclosPaymentService::class)->getCurrencySymbol(),
            'currency_code' => app(CyclosPaymentService::class)->getCurrencyCode()
        ]);
    }

    /**
     * Get complete wallet details for authenticated customer
     */
    public function getWalletDetails(Request $request)
    {
        $user = $request->user();
        $cyclosUserId = $user->cyclos_user_id ?? $user->cyclos_id ?? $user->id;

        if (!$cyclosUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cyclos user ID not found for this customer'
            ], 404);
        }

        $result = $this->posWalletService->getCustomerWalletDetails($cyclosUserId);

        return response()->json($result);
    }

    /**
     * Recharge customer wallet with tokens
     */
    public function customerWalletRecharge(POSCustomerWalletRequest $request)
    {
        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $data = $request->validated();

            $amount = $data['amount'];
            $description = 'Amount added';

            $cyclosUserId = Auth::user()->cyclos_user_id;

            $recharge = $this->cyclosPayment->addFunds($cyclosUserId, $amount, $description);

            DB::commit();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => $amount . ' token added in the wallet successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction history for authenticated customer
     */
    public function showTransaction()
    {
        try {
            $cyclosUserId = Auth::user()->cyclos_user_id;
            $transaction = $this->cyclosPayment->getUserTransactions($cyclosUserId);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Transaction History Fetch Successfully',
                'history' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong' . $e->getMessage()
            ], 500);
        }
    }
}
