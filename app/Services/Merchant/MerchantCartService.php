<?php

namespace App\Services\Merchant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MerchantCartService
{
    protected MerchantProductService $productService;
    protected $cartCacheKey;

    public function __construct(MerchantProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get cart cache key for current user
     */
    protected function getCartKey(): string
    {
        $userId = Auth::id();
        return "merchant_cart_{$userId}";
    }

    /**
     * Get current user's cart
     */
    public function getCart(): array
    {
        $cart = Cache::get($this->getCartKey(), []);
       
        // Enrich cart items with full product details
        $enrichedCart = [];
        foreach ($cart as $productId => $item) {
            try {
                $product = $this->productService->getProduct((string) $productId);
                if ($product) {
                    $enrichedCart[$productId] = [
                        'id' => (string) $productId,
                        'name' => $product['name'] ?? 'Unknown',
                        'price' => $product['price'] ?? 0,
                        'quantity' => $item['quantity'],
                        'image_url' => $product['image_url'] ?? null,
                        'barcode' => $product['barcode'] ?? null,
                        'stock' => $product['quantity'] ?? 0,
                        'pivot' => [
                            'quantity' => $item['quantity'],
                            'product_id' => $productId,
                            'user_id' => Auth::id()
                        ]
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error fetching product for cart: {$productId}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return array_values($enrichedCart);
    }

    /**
     * Add product to cart
     */
    public function addToCart($productId, int $quantity = 1): array
    {
        $cart = Cache::get($this->getCartKey(), []);
        $cyclosUserId = Auth::user()->cyclos_user_id;

        // Get product details to check stock
        $product = $this->productService->getProduct($productId);

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }

        $availableStock = $product['quantity'] ?? 0;

        if ($availableStock < 1) {
            return [
                'success' => false,
                'message' => 'Product is out of stock'
            ];
        }
        if (isset($cart[$productId])) {
            $newQuantity = $cart[$productId]['quantity'] + $quantity;

            if ($availableStock < $newQuantity) {
                return [
                    'success' => false,
                    'message' => "Only {$availableStock} items available"
                ];
            }

            $cart[$productId]['quantity'] = $newQuantity;
        } else {
            $cart[$productId] = [
                'quantity' => $quantity,
                'added_at' => now()
            ];
        }

        Cache::put($this->getCartKey(), $cart, 1440);

        $cart = Cache::get($this->getCartKey(), []);

        return [
            'success' => true,
            'message' => 'Product added to cart'
        ];
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($productId, int $quantity): array
    {
        $cart = Cache::get($this->getCartKey(), []);

        Log::info('Updating cart quantity', [
            'product_id' => $productId,
            'product_id_type' => gettype($productId),
            'cart_keys' => array_keys($cart),
            'cart_data' => $cart
        ]);

        if (!isset($cart[$productId])) {
            return [
                'success' => false,
                'message' => 'Product not found in cart'
            ];
        }

        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }

        // Check stock availability
        $product = $this->productService->getProduct($productId);
        $availableStock = $product['quantity'] ?? 0;

        if ($availableStock < $quantity) {
            return [
                'success' => false,
                'message' => "Only {$availableStock} items available"
            ];
        }

        $cart[$productId]['quantity'] = $quantity;
        Cache::put($this->getCartKey(), $cart, 1440);

        return [
            'success' => true,
            'message' => 'Cart updated'
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(string $productId): array
    {
        $cart = Cache::get($this->getCartKey(), []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Cache::put($this->getCartKey(), $cart, 1440);
        }

        return [
            'success' => true,
            'message' => 'Product removed from cart'
        ];
    }

    /**
     * Empty the cart
     */
    public function emptyCart(): array
    {
        Cache::forget($this->getCartKey());

        return [
            'success' => true,
            'message' => 'Cart emptied'
        ];
    }
}
