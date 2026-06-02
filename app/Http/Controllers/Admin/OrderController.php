<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * List all orders with optional filters.
     */
    public function index(Request $request)
    {
        $query = Order::with('project')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by product
        if ($request->filled('product')) {
            $query->where('project_id', $request->product);
        }

        $orders = $query->paginate(20)->appends($request->query());

        $products = Project::ownProducts()->orderBy('title')->get(['id', 'title']);

        return view('admin.orders.index', compact('orders', 'products'));
    }

    /**
     * Show order details.
     */
    public function show(Order $order)
    {
        $order->load('project');

        return view('admin.orders.show', compact('order'));
    }
}
