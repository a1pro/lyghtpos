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
use App\Models\UserAddress;
use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\Cyclos\CyclosUserService;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\State;
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
        $countries = \App\Models\Country::all();
        return view('auth.register', compact('countries'));
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

        // Create user address
        $this->createUserAddress($user, $request->all());

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
            'phone' => $data['phone'],
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

    /**
     * Create user address from dropdown selections
     */
    protected function createUserAddress(User $user, array $data)
    {
        $cityId = '';
        $stateId = '';
        $countryId = '';
        
        // Get city details with relationships
        if (isset($data['city_id']) && !empty($data['city_id'])) {
            $city = City::find($data['city_id']);
            
            if ($city) {
                $cityId = $city->id;
            }
        }

        if (isset($data['state_id']) && !empty($data['state_id'])) {
            $state = State::find($data['state_id']);

            if ($state) {
                $stateId = $state->id;
            }
        }

        if (isset($data['country_id']) && !empty($data['country_id'])) {
            $country = Country::find($data['country_id']);

            if ($country) {
                $countryId = $country->id;
            }
        }

        UserAddress::create([
            'user_id' => $user->id,
            'address' => $data['address'] ?? '',
            'city' => $cityId,
            'state' => $stateId,
            'postal_code' => $data['postal_code'] ?? '',
            'country' => $countryId,
            'is_active' => true,
        ]);
        
        Log::info('Address created for user', [
            'user_id' => $user->id,
            'city' => $cityId,
            'state' => $stateId,
            'country' => $countryId,
            'postal_code' => $data['postal_code'] ?? ''
        ]);
    }

    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = User::where('email', $email)->exists();
        
        return response()->json($exists ? false : true);
    }

    /**
     * Get states by country ID (for AJAX)
     */
    public function getStates($countryId)
    {
        $states = \App\Models\State::where('country_id', $countryId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('state', 'asc')
            ->get();
        
        return response()->json($states);
    }

    /**
     * Get cities by state ID (for AJAX)
     */
    public function getCities($stateId)
    {
        $cities = \App\Models\City::where('state_id', $stateId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('city', 'asc')
            ->get();
        
        return response()->json($cities);
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