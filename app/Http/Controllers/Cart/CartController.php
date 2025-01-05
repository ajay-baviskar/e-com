<?php

namespace App\Http\Controllers\Cart;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{

    public function addToCart(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            // Get the authenticated user ID
            // $userId = Auth::id();
            $userId = 1;
            // if (!$userId) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'User not authenticated',
            //     ], 401);
            // }

            // Check if the product is already in the cart
            $cartItem = CartItem::where('user_id', $userId)
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($cartItem) {
                // Update quantity if product already exists in the cart
                $cartItem->quantity += $validated['quantity'];
                $cartItem->save();
            } else {
                // Create a new cart item
                $cartItem = CartItem::create([
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'user_id' => $userId,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart',
                'cart_item' => $cartItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewCart()
    {
        try {
            // Get the authenticated user ID
            // $userId = Auth::id();

            // if (!$userId) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'User not authenticated',
            //     ], 401);
            // }
            $userId = 1;

            // Fetch cart items with related product and images
            $cartItems = CartItem::with('product.images')
                ->where('user_id', $userId)
                ->get();

            return response()->json([
                'status' => 'success',
                'cart_items' => $cartItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch cart items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
