<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderMatchingService;

class OrderController extends Controller
{
    protected $orderService;
    
    public function __construct(OrderMatchingService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    public function index(Request $request)
    {
        $query = Order::where('status', Order::STATUS_OPEN);
        
        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }
        
        return response()->json([
            'orders' => $query->orderBy('created_at', 'desc')->get(),
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|in:BTC,ETH',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);
        
        try {
            $order = $this->orderService->createOrder(
                $request->user(),
                $validated
            );
            
            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        try {
            $order = $this->orderService->cancelOrder($order);
            
            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    public function userOrders(Request $request)
    {
        $orders = $request->user()->orders()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json(['orders' => $orders]);
    }
}
