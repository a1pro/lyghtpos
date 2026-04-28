<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\Log;

class AgreementController extends Controller
{
    protected $cyclosUserService;

    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->cyclosUserService = $cyclosUserService;
    }

    /**
     * Display list of pending agreements
     */
    public function index(Request $request)
    {
        $pendingAgreements = $request->session()->get('pending_agreements', []);
        $credentials = $request->session()->get('cyclos_credentials');

        if (empty($pendingAgreements)) {
            // No pending agreements, redirect to home
            return redirect()->route('merchant.cart.index');
        }

        return view('merchant.pages.agreements.index', [
            'agreements' => $pendingAgreements,
            'credentials' => $credentials
        ]);
    }

    /**
     * Get agreement content for modal popup
     */
    public function getContent(Request $request, $agreementId)
    {
        $credentials = $request->session()->get('cyclos_credentials');

        if (!$credentials) {
            return response()->json(['error' => 'Credentials not found'], 401);
        }

        $agreementContent = $this->cyclosUserService->getAgreementContent(
            $credentials['username'],
            $credentials['password'],
            $agreementId
        );

        if (!$agreementContent) {
            return response()->json(['error' => 'Failed to fetch agreement content'], 500);
        }

        return response()->json($agreementContent);
    }

    /**
     * Accept multiple agreements
     */
    public function accept(Request $request)
    {
        $request->validate([
            'agreements' => 'required|array',
            'agreements.*' => 'string'
        ]);

        $credentials = $request->session()->get('cyclos_credentials');

        if (!$credentials) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        // Accept all selected agreements
        $success = $this->cyclosUserService->acceptAgreements(
            $credentials['username'],
            $credentials['password'],
            $request->agreements
        );

        if ($success) {
            // Clear session data and mark agreements as accepted
            $request->session()->forget(['pending_agreements', 'cyclos_credentials']);
            $request->session()->put('cyclos_agreements_accepted', true);

            return redirect()->intended(route('merchant.cart.index'))
                ->with('success', 'All agreements have been accepted successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to accept agreements. Please try again.');
    }
}
