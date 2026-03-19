<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserGovernanceLayer;
use App\Models\GovernanceLayer;

class MerchantRepresentationController extends Controller
{
    public function showForm()
    {
        return view('merchant.pages.representations');
    }
    public function submitForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'community' => ['required', 'string'],
            'role' => ['required', 'string'],
            'accepted_currencies' => ['required', 'array', 'min:1'],
            'accepted_currencies.*' => ['string'],
            'participate_vouchers' => ['nullable', 'boolean'],
            'allow_barter' => ['nullable', 'boolean'],
            'pledge_description' => ['nullable', 'string', 'max:1000'],
            'pledge_value' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $participateVouchers = $request->has('participate_vouchers') ? 1 : 0;
        $allowBarter = $request->has('allow_barter') ? 1 : 0;

        // Create or update the representation
        $representation = Representation::updateOrCreate(
            [
                'user_id' => $request->input('user_id'),
            ],
            [
                'community' => $request->input('community'),
                'role' => $request->input('role'),
                'accepted_currencies' => json_encode($request->input('accepted_currencies')),
                'participate_vouchers' => $participateVouchers,
                'allow_barter' => $allowBarter,
                'pledge_description' => $request->input('pledge_description'),

                'pledge_value' => $request->input('pledge_value'),
                'status' => 'pending',
            ]
        );

        // Governance layer logic moved here with representation_id
        $governanceData = GovernanceLayer::where('status', 'active')->get();

        foreach ($governanceData as $govData) {
            UserGovernanceLayer::create([
                'user_id'  => $request->input('user_id'),
                'governance_id' => $govData->id,
                'representation_id' => $representation->id // Added representation_id
            ]);
        }

        return redirect()->route('merchant.dashboard');
    }
}
