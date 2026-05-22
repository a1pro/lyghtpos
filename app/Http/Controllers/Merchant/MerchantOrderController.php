<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantOrder;
use App\Services\Merchant\MerchantOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class MerchantOrderController extends Controller
{
    /**
     * Display a listing of orders for the authenticated merchant.
     */
    public function index(Request $request): View|JsonResponse
    {
        $merchantId = Auth::id();

        $orders = MerchantOrder::with('items') // eager load items
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalAmount = $orders->sum('total_amount');

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'total' => $totalAmount
            ]);
        }

        return view('merchant.pages.orders.index', compact('orders', 'totalAmount'));
    }

    /**
     * Display the specified order details.
     */
    public function show(MerchantOrder $order): View
    {
        if ((int) $order->merchant_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this order.');
        }

        // Load items relationship
        $order->load(['items']);

        $customer = $order->customer();

        return view('merchant.pages.orders.view', compact('order', 'customer'));
    }
}
