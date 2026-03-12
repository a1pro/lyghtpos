<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierStoreRequest;
use App\Http\Requests\Supplier\SupplierUpdateRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

class SupplierController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        if($user->role_id==1)
    {
        $suppliers = Supplier::latest()->paginate();
    }
    else
    {
        $suppliers = Supplier::where('user_id',$user->id)->latest()->paginate();
    }
        if ($request->wantsJson()) {
            return response()->json($suppliers);
        }

        return view('suppliers.index', ['suppliers' => $suppliers]);
    }

    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('suppliers.create');
    }

    public function store(SupplierStoreRequest $request)
    {
        $supplierData = $request->validated();
    
        if ($request->hasFile('avatar')) {
            $supplierData['avatar'] = $request->file('avatar')->store('suppliers', 'public');
        }
        
        // Add the authenticated user's ID
        $supplierData['user_id'] = auth()->id();
        
        Supplier::create($supplierData);
    
        $route = auth()->user()->isAdmin()
            ? 'suppliers.index'
            : 'merchant.suppliers.index';
        
        return redirect()
            ->route($route)
            ->with('success', __('supplier.success_creating'));
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier)
    {
        $supplierData = $request->validated();
        if ($request->hasFile('avatar')) {

            if ($supplier->avatar) {
                Storage::disk('public')->delete($supplier->avatar);
            }
            $supplierData['avatar'] = $request->file('avatar')->store('suppliers', 'public');
        }

        $supplier->update($supplierData);

        return redirect()->route('suppliers.index')
            ->with('success', __('supplier.success_updating'));
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->avatar) {
            Storage::disk('public')->delete($supplier->avatar);
        }

        $supplier->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
