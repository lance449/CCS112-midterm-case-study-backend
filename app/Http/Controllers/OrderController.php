<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get cart items
            $cartItems = CartItem::with('product')
                ->where('user_id', $request->user()->id)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Cart is empty'], 400);
            }

            // Check product quantities and update stock
            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);
                
                if ($product->quantity < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Insufficient stock for {$product->description}",
                        'available' => $product->quantity
                    ], 400);
                }

                // Update product quantity
                $product->quantity -= $item->quantity;
                $product->save();
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'total' => $cartItems->sum(function ($item) {
                    return $item->quantity * $item->product->price;
                }),
                'status' => 'pending'
            ]);

            // Clear cart
            CartItem::where('user_id', $request->user()->id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error processing checkout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 