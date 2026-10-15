<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Mail\Merchant\Customers\NewCustomerCreated;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Services\Merchant\MerchantPlansConditionService;
use App\Models\Customer;
use App\Models\Country;
use App\Models\User;
use App\Services\Merchant\MerchantCustomerService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    protected MerchantCustomerService $merchantCustomerService;
    protected MerchantPlansConditionService $merchantPlanCondition;

    public function __construct(
        MerchantCustomerService $merchantCustomerService,
        MerchantPlansConditionService $merchantPlanCondition
    ) {
        $this->merchantCustomerService = $merchantCustomerService;
        $this->merchantPlanCondition  = $merchantPlanCondition;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|View
    {
        $search = trim($request->input('search', ''));

        $customers = User::where('merchant_id', Auth::id())
            ->with('address')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();   // keeps ?search=... in pagination links

        return view('merchant.pages.customers.index', compact('customers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View|Factory
    {
        $countries = Country::orderBy('name', 'asc')->get();

        return view('merchant.pages.customers.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(CustomerStoreRequest $request)
    {
        $data = $request->validated();

        // Plan limit check
        $customerAdd = $this->merchantPlanCondition->canAddCustomer(Auth::id());
        if (!$customerAdd['can_add']) {
            return back()
                ->withInput()
                ->with('error', $customerAdd['message']);
        }

        try {
            $data['merchant_id'] = Auth::id();

            $this->merchantCustomerService->createCustomer($data);

            return redirect()
                ->route('merchant.customers.index')
                ->with('success', 'Customer created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Customer creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong while creating customer. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $customer): Factory|View
    {
        // Make sure the merchant owns this customer
        abort_if($customer->merchant_id !== Auth::id(), 403);

        return view('merchant.pages.customers.edit', ['customer' => $customer]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CustomerUpdateRequest $request, User $customer)
    {
        abort_if($customer->merchant_id !== Auth::id(), 403);

        $customerData = $request->validated();

        // if ($request->hasFile('avatar')) {
        //     if ($customer->avatar) {
        //         Storage::disk('public')->delete($customer->avatar);
        //     }

        //     $customerData['avatar'] = $request->file('avatar')->store('customers', 'public');
        // }

        $customer->update($customerData);

        return redirect()->route('merchant.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Generate a new password and email it to the customer.
     */
    public function resendCredentials(User $customer)
    {
        abort_if($customer->merchant_id !== Auth::id(), 403);

        try {
            $plainPassword = Str::random(12);

            $customer->update([
                'password' => Hash::make($plainPassword),
            ]);

            Mail::to($customer->email)
                ->queue(new NewCustomerCreated($customer, $plainPassword));

            return back()->with('success', 'New credentials sent to ' . $customer->email);
        } catch (\Throwable $e) {
            Log::error('Failed to resend customer credentials', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send credentials. Please try again.');
        }
    }
}
