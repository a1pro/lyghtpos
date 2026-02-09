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

  
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => ['required','string', 'max:255'],
            'primary_contact_name'=> ['required','string', 'max:255'],
            'phone'=> ['required','string','max:255'],
            'location'=> ['required','string','max:255'],
            'website'=>['required','string','max:255'],
            'consent_language_authorizing'=>['required','string','max:255'],
            'communyti_enrollment'=> ['required','string','max:255'],
            'advocate_contact'=> ['required','string','max:255'],
            'subject' => ['nullable', 'string', 'max:191'],
            'purpose' => ['nullable', 'string', 'max:191'],
            'futuristic_plan' => ['nullable', 'string', 'max:191'],
            'pledge_value' => ['required', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:191'],
        ]);
    }

    
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    
    public function register(Request $request)
    {
        
        if ($request->has('step') && $request->step == '1') {
            
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'business_name' => ['required','string', 'max:255'],
                'primary_contact_name'=> ['required','string', 'max:255'],
                'phone'=> ['required','string','max:255'],
                'location'=> ['required','string','max:255'],
                'website'=>['required','string','max:255'],
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
           
            Session::put('registration.step1', $request->except('_token', 'step'));
            
            
            return redirect()->route('register.representation');
        }
  
        if (!Session::has('registration.step1')) {
            return redirect()->route('register');
        }
        
        $step1Data = Session::get('registration.step1');
        
      
        $allData = array_merge($step1Data, $request->all());
        
        
        $this->validator($allData)->validate();
        
        $user = $this->create($allData);
        
        Representation::create([
            'user_id' => $user->id,
            'subject' => $request->input('subject'),
            'purpose' => $request->input('purpose'),
            'futuristic_plan' => $request->input('futuristic_plan'),
            'pledge_value' => $request->input('pledge_value'),
            'currency' => $request->input('currency'),
        ]);
        
        
        Session::forget('registration.step1');
        
        
        
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
            'business_name' => $data['business_name'],
            'primary_contact_name' => $data['primary_contact_name'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'website' => $data['website'],
            'consent_language_authorizing' => $data['consent_language_authorizing'],
            'communyti_enrollment' => $data['communyti_enrollment'],
            'advocate_contact' => $data['advocate_contact'],
        ]);
        
        Merchant::create([
            'user_id' => $user->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'merchant_id' => $merchantId,
        ]);
        
        return $user;
    }
    
    
    public function showRepresentationForm()
    {
        if (!Session::has('registration.step1')) {
            return redirect()->route('register');
        }
        
        return view('merchant.pages.representations');
    }
}