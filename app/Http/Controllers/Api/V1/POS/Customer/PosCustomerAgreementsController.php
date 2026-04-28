<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Customer\PosCustomerAcceptAgreementRequest;
use App\Http\Requests\Api\POS\Customer\PosCustomerAgreementContentRequest;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\Auth;

class PosCustomerAgreementsController extends Controller
{
    protected $cyclosUserService;

    /**
     * Inject cyclos user service dependency
     */
    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->cyclosUserService = $cyclosUserService;
    }

    /**
     * Get all pending Agreements for authenticated customer
     */
    public function getPendingAgreements()
    {
        try {
            $cyclosUsername = Auth::user()->cyclos_username;
            $cyclosUserPassword = Auth::user()->cyclos_user_password;

            if (!$cyclosUsername || !$cyclosUserPassword) {
                throw new \Exception("Cyclos User not found for this user");
            }

            $data = $this->cyclosUserService->getUserAgreements($cyclosUsername, $cyclosUserPassword);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Pending agreements retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending Agreements for authenticated customer
     */
    public function getAgreementContent(PosCustomerAgreementContentRequest $request)
    {
        try {
            $cyclosUsername = Auth::user()->cyclos_username;
            $cyclosUserPassword = Auth::user()->cyclos_user_password;

            if (!$cyclosUsername || !$cyclosUserPassword) {
                throw new \Exception("Cyclos User not found for this user");
            }

            $data = $this->cyclosUserService->getAgreementContent($cyclosUsername, $cyclosUserPassword, $request->validated('agreement_id'));

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Agreement content retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending Agreements for authenticated customer
     */
    public function acceptAgreement(PosCustomerAcceptAgreementRequest $request)
    {
        try {
            $cyclosUsername = Auth::user()->cyclos_username;
            $cyclosUserPassword = Auth::user()->cyclos_user_password;

            if (!$cyclosUsername || !$cyclosUserPassword) {
                throw new \Exception("Cyclos User not found for this user");
            }

            $data = $this->cyclosUserService->acceptAgreements($cyclosUsername, $cyclosUserPassword, $request->validated('agreement_ids'));

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Agreements accepted successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }
}
