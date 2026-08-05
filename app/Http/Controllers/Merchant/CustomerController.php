<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    public function index(): Response|View
    {
        $customers = User::where('merchant_id', Auth::id())->with('address')->latest()->paginate(10);

        return view('merchant.pages.customers.index')->with('customers', $customers);
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
        try {
            $data = $request->validated();
            $userId = Auth::id();
            $customerAdd = $this->merchantPlanCondition->canAddCustomer($userId);

            // Check using 'can_add' key (or 'success' key)
            if (!$customerAdd['can_add']) {  // Or use if (!$customerAdd['success'])
                return back()
                    ->withInput()
                    ->with('error', $customerAdd['message']);
            }

            $data['merchant_id'] = Auth::id();

            $this->merchantCustomerService->createCustomer($data);

            return redirect()->route('merchant.customers.index')
                ->with('success', 'Customer created successfully.');
        } catch (\Throwable $e) {
            Log::error('Customer creation failed', [
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong while creating customer.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(Customer $customer): Factory|View
    // {
    //     return view('merchant.pages.customers.edit', ['customer' => $customer]);
    // }

    /**
     * Update the specified resource in storage.
     *
     */
    // public function update(CustomerUpdateRequest $request, Customer $customer)
    // {
    //     $user = Auth::user();
    //     $customerData = $request->validated();

    //     if ($request->hasFile('avatar')) {
    //         if ($customer->avatar) {
    //             Storage::disk('public')->delete($customer->avatar);
    //         }

    //         $customerData['avatar'] = $request->file('avatar')->store('customers', 'public');
    //     }

    //     $customer->update($customerData);

    //     return redirect()->route('merchant.pages.customers.index')
    //         ->with('success', 'Customer updated successfully.');
    // }
}
