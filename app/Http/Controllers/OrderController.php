<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Función para crear una nueva orden
    public function storeOrder(Request $request)
    {
        // Validate the required fields
        $validated = $request->validate([
            'orderId' => 'required|string|unique:orders',
            'symbol' => 'required|string',
            'side' => 'required|string',
            'price' => 'required|numeric',
            'origQty' => 'required|numeric',
            'executedQty' => 'required|numeric',
            'status' => 'required|string',
            'type' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        // Create a new order using the validated data
        $order = Order::create([
            'orderId' => $validated['orderId'],
            'symbol' => $validated['symbol'],
            'side' => $validated['side'],
            'price' => $validated['price'],
            'origQty' => $validated['origQty'],
            'executedQty' => $validated['executedQty'],
            'status' => $validated['status'],
            'type' => $validated['type'],
            'user_id' => $validated['user_id'],
            'binance_order_data' => json_encode($request->all()), // Store the whole order object as JSON
        ]);

        return response()->json([
            'message' => 'Order created successfully!',
            'order' => $order
        ], 201);
    }
    // after the typical CRUD:


    public function showOrderByOrderId($orderId)
    {
        $order = Order::findByOrderId($orderId);

        if ($order) {
            return response()->json($order);
        } else {
            return response()->json(['message' => 'Order not found'], 404);
        }
    }

    
    // Función para obtener las últimas X orders
    public function getLastOrders(Request $request)
    {
        // Get the limit from the request, default to 10 if not provided
        $limit = $request->query('limit', 10);
        
        // Get the current authenticated user
        $user = $request->user();
        
        // Return the orders for the current user, ordered by latest
        return response()->json(
            Order::where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
        );
    }


}
