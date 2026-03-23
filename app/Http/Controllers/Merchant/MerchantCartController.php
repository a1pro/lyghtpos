<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\ChangeQuantityRequest;
use App\Http\Requests\Cart\RemoveFromCartRequest;
use App\Services\Merchant\MerchantCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MerchantCartController extends Controller
{
    protected MerchantCartService $cartService;

    public function __construct(MerchantCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            $cart = $this->cartService->getCart();
            return response()->json($cart);
        }

        return view('merchant.pages.cart.index');
    }

    public function checkout(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            //
        }

        return view('merchant.pages.checkout.index');
    }

    /**
     * Add product to cart.
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        $productId = $request->product_id;

        if (!$productId) {
            return response()->json([
                'message' => 'Product ID is required'
            ], 400);
        }
        
        $result = $this->cartService->addToCart($productId, 1, Auth::user()->cyclos_user_id);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Change quantity of a cart item.
     */
    public function changeQty(ChangeQuantityRequest $request): JsonResponse
    {
        $result = $this->cartService->updateQuantity(
            $request->product_id,
            $request->quantity
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove product from cart.
     */
    public function delete(RemoveFromCartRequest $request): JsonResponse
    {
        $result = $this->cartService->removeFromCart($request->product_id);

        return response()->json(['success' => $result['success']]);
    }

    /**
     * Empty the entire cart.
     */
    public function empty(Request $request): JsonResponse
    {
        $result = $this->cartService->emptyCart();

        return response()->json(['success' => $result['success']]);
    }
}
