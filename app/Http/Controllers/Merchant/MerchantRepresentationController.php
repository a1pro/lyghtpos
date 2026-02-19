<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        Representation::create([
            'user_id' => $request->input('user_id'),
            'subject' => $request->input('subject'),
            'purpose' => $request->input('purpose'),
            'futuristic_plan' => $request->input('futuristic_plan'),
            'pledge_value' => $request->input('pledge_value'),
            'currency' => $request->input('currency'),
            'status' => 'approved'
        ]);

        return redirect()->route('merchant.dashboard');
    }
}
