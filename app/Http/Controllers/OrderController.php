<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'customer_name' => 'required|string',
                'shipping_address' => 'required|string',
                'payment_method' => 'required|string',
                'contact_number' => 'required|string',
                'items' => 'required|array'
            ]);

            // Calculate total amount from items
            $totalAmount = collect($request->items)->sum(function($item) {
                return $item['price'] * $item['quantity'];
            });

            // Create the order with calculated total
            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $request->customer_name,
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'contact_number' => $request->contact_number,
                'total_amount' => $totalAmount,  // Save the calculated total
                'status' => 'pending'
            ]);

            // Process each ordered item
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product not found");
                }

                // Update product quantity
                $newQuantity = $product->quantity - $item['quantity'];
                if ($newQuantity < 0) {
                    throw new \Exception("Not enough stock for " . $product->description);
                }
                $product->quantity = $newQuantity;
                $product->save();

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }

            // Clear the user's cart
            CartItem::where('user_id', auth()->id())->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => $order->load('items')  // Return order with items
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $orders = Order::with(['items.product'])
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Log the orders for debugging
            \Log::info('Orders with totals:', $orders->toArray());

            return response()->json($orders);
        } catch (\Exception $e) {
            \Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 