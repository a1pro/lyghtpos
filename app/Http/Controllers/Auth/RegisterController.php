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
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\Cyclos\CyclosUserService;
use App\Http\Requests\Auth\RegisterRequest;
use Log;

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

    public function register(RegisterRequest $request)
    {
        $validator = Validator::make($request->all(), $request->rules());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create the user
        $user = $this->create($request->all());

        // For FAVOR_MEMBER, directly create Cyclos account and update user
        if ($request->account_type === 'FAVOR_MEMBER') {
            $cyclosData = $this->createCyclosUser($user);
            
            // Update user with Cyclos data if creation was successful
            if ($cyclosData && isset($cyclosData['user_id'])) {
                $user->update([
                    'cyclos_user_id' => $cyclosData['user_id'],
                    'cyclos_username' => $cyclosData['username'] ?? null,
                    'cyclos_user_password' => $cyclosData['password'] ?? null,
                    'cyclos_group' => $cyclosData['group'] ?? null,
                ]);
                
                Log::info('Favor member updated with Cyclos data', [
                    'user_id' => $user->id,
                    'cyclos_user_id' => $cyclosData['user_id']
                ]);
            }
        }

        // Add success message and redirect to login
        return redirect()->route('login')
            ->with('success', 'Registration successful! Please login with your credentials.');
    }

    protected function create(array $data)
    {
        $merchantId = Str::random(10);

        $roleMap = [
            'TRADE_MERCHANT' => 'merchant',
            'TRADE_MEMBER'   => 'trade_member',
            'ORGANIZATION'   => 'organization',
            'FAVOR_MEMBER'   => 'favor_member',
        ];

        $role = Role::where('name', $roleMap[$data['account_type']])->first();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id ?? 2,
            'account_type' => $data['account_type'],
        ]);

        // Create merchant record for TRADE_MERCHANT
        if ($data['account_type'] === 'TRADE_MERCHANT') {
            Merchant::create([
                'user_id' => $user->id,
                'merchant_id' => $merchantId,
                'business_name' => $data['business_name'],
                'primary_contact_name' => $data['primary_contact_name'],
                'phone' => $data['phone'],
                'location' => $data['location'],
                'website' => $data['website'] ?? '',
                'consent_language_authorizing' => $data['consent_language_authorizing'] ?? '',
                'communyti_enrollment' => $data['communyti_enrollment'] ?? false,
                'advocate_contact' => $data['advocate_contact'] ?? '',
            ]);
        }

        return $user;
    }
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = User::where('email', $email)->exists();
        
        return response()->json($exists ? false : true);
    }
    /**
     * Create Cyclos user directly for favor members
     */
    protected function createCyclosUser(User $user): ?array
    {
        try {
            $cyclosService = app(CyclosUserService::class);
            $response = $cyclosService->createUser($user);

            if ($response && isset($response['user_id'])) {
                Log::info('Cyclos user created directly for favor member', [
                    'user_id' => $user->id,
                    'cyclos_user_id' => $response['user_id']
                ]);
                return $response;
            } else {
                Log::error('Failed to create Cyclos user for favor member', [
                    'user_id' => $user->id
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while creating Cyclos user for favor member', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}