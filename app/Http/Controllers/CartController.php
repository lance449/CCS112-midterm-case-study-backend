<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class CartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cartItems = CartItem::with('product')
                ->where('user_id', $request->user()->id)
                ->get();
            
            return response()->json($cartItems);
        } catch (\Exception $e) {
            Log::error('Cart fetch error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching cart items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $cartItem = CartItem::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'product_id' => $validated['product_id']
                ],
                ['quantity' => $validated['quantity']]
            );

            DB::commit();
            return response()->json($cartItem->load('product'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cart error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to add to cart'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            CartItem::where('user_id', auth()->id())
                ->where('id', $id)
                ->delete();

            return response()->json(['message' => 'Item removed from cart']);
        } catch (\Exception $e) {
            Log::error('Cart item delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error removing item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clear()
    {
        try {
            CartItem::where('user_id', auth()->id())->delete();
            return response()->json(['message' => 'Cart cleared']);
        } catch (\Exception $e) {
            Log::error('Cart clear error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error clearing cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $cartItem = CartItem::where('user_id', auth()->id())
                ->where('id', $id)
                ->firstOrFail();

            $cartItem->update([
                'quantity' => $request->quantity
            ]);

            return response()->json([
                'message' => 'Cart updated successfully',
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
