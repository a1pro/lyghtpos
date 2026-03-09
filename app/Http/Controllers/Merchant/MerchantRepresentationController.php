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
            'subject' => ['required', 'string', 'max:255'],
            'pledge_value' => ['required'],
            'currency' => ['required'],
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
    
        // Create or update the representation
        $representation = Representation::updateOrCreate(
            [
                'user_id' => $request->input('user_id'),
                'subject' => $request->input('subject'),
            ],
            [
                'purpose' => $request->input('purpose'),
                'futuristic_plan' => $request->input('futuristic_plan'),
                'pledge_value' => $request->input('pledge_value'),
                'currency' => $request->input('currency'),
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
