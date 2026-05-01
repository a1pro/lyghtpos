<?php

namespace App\Services\Merchant;

use App\Mail\Merchant\Customers\NewCustomerCreated;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Customer;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MerchantCustomerService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;
    protected string $currencyCode;
    protected string $currencySymbol;
    protected string $currencyName;
    protected string $adminUserId;
    protected ?array $marketplaceData = null;

    protected CyclosUserService $cyclosUserService;

    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->baseUrl = rtrim((string) config('services.cyclos.base_url'), '/');
        $this->username = (string) config('services.cyclos.username');
        $this->password = (string) config('services.cyclos.password');
        $this->channel = (string) config('services.cyclos.channel', 'webServices');

        $this->currencyCode = (string) config('services.cyclos.currency_code', 'FT');
        $this->currencySymbol = (string) config('services.cyclos.currency_symbol', 'FT');
        $this->currencyName = (string) config('services.cyclos.currency_name', 'FavorTokens');
        $this->adminUserId = (string) config('services.cyclos.admin_user_id');

        $this->cyclosUserService = $cyclosUserService;

        $this->validateRequiredConfig();
    }

    protected function validateRequiredConfig(): void
    {
        $missing = [];

        if (empty($this->baseUrl)) {
            $missing[] = 'services.cyclos.base_url';
        }

        if (empty($this->username)) {
            $missing[] = 'services.cyclos.username';
        }

        if (empty($this->password)) {
            $missing[] = 'services.cyclos.password';
        }

        if (empty($this->adminUserId)) {
            $missing[] = 'services.cyclos.admin_user_id';
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required Cyclos configuration: ' . implode(', ', $missing)
            );
        }
    }

    public function createCustomer(array $data)
    {
        DB::beginTransaction();

        try {
            $plainPassword = Str::random(12);

            $country = Country::where('country_id', $data['country_id'])->first();
            $state = State::where('id_state', $data['state_id'])
                ->where('country_id', $data['country_id'])
                ->first();

            $city = City::where('id_city', $data['city_id'])
                ->where('state_id', $data['state_id'])
                ->first();

            if (!$country || !$state || !$city) {
                throw new Exception('Invalid country, state, or city selected.');
            }

            $user = User::create([
                'merchant_id' => $data['merchant_id'],
                'role_id' => 11,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($plainPassword),
            ]);

            $cyclosUserResponse = $this->cyclosUserService->createUser($user);

            if (!$cyclosUserResponse || !isset($cyclosUserResponse['user_id'])) {
                throw new Exception('Cyclos user creation failed');
            }

            $user->update([
                'cyclos_user_id' => $cyclosUserResponse['user_id'],
                'cyclos_group' => $cyclosUserResponse['group'] ?? null,
                'cyclos_username' => $cyclosUserResponse['username'] ?? null,
                'cyclos_user_password' => $cyclosUserResponse['password'] ?? null,
            ]);

            $forceAcceptAgreementsResponse = $this->cyclosUserService->forceAcceptAgreements(
                $cyclosUserResponse['username'],
                $cyclosUserResponse['password']
            );

            if (!$forceAcceptAgreementsResponse['success']) {
                Log::info('Something went wrong while force accepting user agreements', [
                    'forceAcceptAgreementsResponse' => $forceAcceptAgreementsResponse
                ]);
            } else {
                Log::info('Force accepted user agreements successfully', [
                    'forceAcceptAgreementsResponse' => $forceAcceptAgreementsResponse
                ]);
            }

            $customerData = $data;

            unset(
                $customerData['country_id'],
                $customerData['state_id'],
                $customerData['city_id'],
                $customerData['postal_code']
            );

            $customer = Customer::create([
                ...$customerData,
                'user_id' => $data['merchant_id'],
            ]);

            UserAddress::create([
                'user_id' => $user->id,
                'address' => $data['address'] ?? '',
                'city' => $city->city,
                'state' => $state->state,
                'postal_code' => $data['postal_code'] ?? '',
                'country' => $country->country_name,
                'is_active' => true,
            ]);

            Log::info('Address created for customer user', [
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'city' => $city->city,
                'state' => $state->state,
                'country' => $country->country_name,
                'postal_code' => $data['postal_code'] ?? '',
            ]);

            DB::commit();

            Mail::to($user->email)
                ->queue(new NewCustomerCreated($user, $plainPassword));

            return $customer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createPaxCustomer(array $data)
    {
        DB::beginTransaction();

        try {
            $plainPassword = Str::random(12);
            $user = User::create([
                'merchant_id' => $data['merchant_id'],
                'role_id' => 11,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($plainPassword),
            ]);

            UserAddress::create([
                'user_id' => $user->id,
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
            ]);

            $cyclosUserResponse = $this->cyclosUserService->createUser($user);

            if (!$cyclosUserResponse || !isset($cyclosUserResponse['user_id'])) {
                throw new Exception('Cyclos user creation failed');
            }

            $user->update([
                'cyclos_user_id' => $cyclosUserResponse['user_id'],
                'cyclos_group' => $cyclosUserResponse['group'] ?? null,
                'cyclos_username' => $cyclosUserResponse['username'] ?? null,
                'cyclos_user_password' => $cyclosUserResponse['password'] ?? null,
            ]);

            $forceAcceptAgreementsResponse = $this->cyclosUserService->forceAcceptAgreements($cyclosUserResponse['username'], $cyclosUserResponse['password']);

            if (!$forceAcceptAgreementsResponse['success']) {
                Log::info("Something went wrong while force accepting user agreements", [
                    "forceAcceptAgreementsResponse" => $forceAcceptAgreementsResponse
                ]);
            } else {
                Log::info("Force accepted user agreements successfully", [
                    "forceAcceptAgreementsResponse" => $forceAcceptAgreementsResponse
                ]);
            }

            $customer = Customer::create([
                ...$data,
                'user_id' => $data['merchant_id'],
            ]);

            DB::commit();

            Mail::to($user->email)
                ->queue(new NewCustomerCreated($user, $plainPassword));

            return $customer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
