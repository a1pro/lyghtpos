<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\MerchantOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class MerchantOrderController extends Controller
{
    protected MerchantOrderService $orderService;

    public function __construct(MerchantOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display all orders related to merchant.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();

        $cyclosUserId = $user->cyclos_user_id;

        // Prepare filters
        $filters = [];
        if ($request->has('start_date')) {
            $filters['start_date'] = $request->start_date;
        }
        if ($request->has('end_date')) {
            $filters['end_date'] = $request->end_date;
        }

        $filters['sales'] = "true";

        // Get orders - the service returns array with 'orders' and 'headers'
        $result = $this->orderService->getUserOrders($cyclosUserId, $filters);

        $orders = $result['orders'] ?? [];
        $headers = $result['headers'] ?? [];

        // Calculate totals for display
        $totalAmount = collect($orders)->sum(function ($order) {
            return floatval($order['totalPrice'] ?? 0);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'headers' => $headers,
                'total' => $totalAmount
            ]);
        }

        return view("merchant.pages.orders.index", compact('orders', 'totalAmount', 'headers'));
    }
}
