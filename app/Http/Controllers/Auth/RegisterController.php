<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Merchant;
use App\Models\Representation;
use App\Models\UserGovernanceLayer;
use App\Models\GovernanceLayer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
{
    use RegistersUsers;


    protected string $redirectTo = '/login';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'business_name' => ['required', 'string', 'max:255'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'website' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->create($request->all());

        // Add success message and redirect to login
        return redirect()->route('login')
            ->with('success', 'Registration successful! Please login with your credentials.');
    }

    protected function create(array $data)
    {
        $merchantId = Str::random(10);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 2,
        ]);

        
        Merchant::create([
            'user_id' => $user->id,
            'merchant_id' => $merchantId,
            'business_name' => $data['business_name'],
            'primary_contact_name' => $data['primary_contact_name'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'website' => $data['website'],
            'consent_language_authorizing' => $data['consent_language_authorizing'],
            'communyti_enrollment' => $data['communyti_enrollment'],
            'advocate_contact' => $data['advocate_contact'],
        ]);

        $governanceData = GovernanceLayer::where('status','active')->get();
        foreach($governanceData as $govData){
            UserGovernanceLayer::create([
                'user_id'  => $user->id,
                'governance_id' => $govData->id
            ]);
        }

        return $user;
    }
}
