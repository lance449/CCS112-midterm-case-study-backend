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
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'is_new' => 'boolean'
            ]);

            // Check if cart item already exists
            $existingItem = CartItem::where('user_id', $request->user()->id)
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($existingItem) {
                // Update existing item's quantity
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $validated['quantity']
                ]);
                return response()->json([
                    'message' => 'Cart item quantity updated',
                    'cart_item' => $existingItem->load('product')
                ]);
            }

            // Create new cart item
            $cartItem = CartItem::create([
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity']
            ]);

            return response()->json([
                'message' => 'Item added to cart',
                'cart_item' => $cartItem->load('product')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Cart store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error adding item to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cartItem = CartItem::where('user_id', auth()->id())
                ->where('id', $id)
                ->firstOrFail();

            $cartItem->delete();

            return response()->json([
                'message' => 'Item removed from cart successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error removing cart item: ' . $e->getMessage());
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
            \Log::info('Cart update request received', [
                'id' => $id,
                'user_id' => $request->user()->id,
                'quantity' => $request->input('quantity')
            ]);

            // Validate the request
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            // Find and update the cart item
            $cartItem = CartItem::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            // Make sure quantity is in the fillable array in CartItem model
            $cartItem->fill([
                'quantity' => $validated['quantity']
            ]);
            
            // Force save the changes
            $cartItem->save();

            // Refresh the model from database to verify changes
            $cartItem->refresh();

            \Log::info('Cart item updated successfully', [
                'id' => $id,
                'new_quantity' => $cartItem->quantity,
                'validated_quantity' => $validated['quantity']
            ]);

            return response()->json([
                'message' => 'Cart item updated successfully',
                'cart_item' => $cartItem->load('product')
            ]);
        } catch (\Exception $e) {
            \Log::error('Cart update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error updating cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
