@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Orders</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage product purchases and payments</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="admin-card p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div>
                <select name="product" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm">Filter</button>
            @if(request()->hasAny(['status', 'product']))
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
            @endif
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        @php
            $totalPaid = \App\Models\Order::paid()->count();
            $totalRevenue = \App\Models\Order::paid()->sum('amount');
            $totalPending = \App\Models\Order::pending()->count();
            $totalFailed = \App\Models\Order::where('status', 'failed')->count();
        @endphp
        <div class="admin-card p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid Orders</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $totalPaid }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $totalPending }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Failed</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $totalFailed }}</p>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="admin-card overflow-hidden">
        @if($orders->count() > 0)
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Product</th>
                    <th>Tier</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>
                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($order->order_token, 12) }}</span>
                    </td>
                    <td>
                        <span class="font-medium text-gray-800 dark:text-white">{{ $order->project->title ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $order->tier_name }}</td>
                    <td>
                        @if($order->customer_email)
                        <span class="text-sm">{{ $order->customer_email }}</span>
                        @else
                        <span class="text-gray-400 text-sm">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="font-semibold">${{ number_format($order->amount, 2) }}</span>
                    </td>
                    <td>
                        @switch($order->status)
                            @case('paid')
                                <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Paid</span>
                                @break
                            @case('pending')
                                <span class="status-badge bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Pending</span>
                                @break
                            @case('failed')
                                <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                @break
                            @case('cancelled')
                                <span class="status-badge bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">Cancelled</span>
                                @break
                            @case('refunded')
                                <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">Refunded</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y') }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
            </svg>
            <p class="font-medium">No orders yet</p>
            <p class="text-sm mt-1">Orders will appear here when customers make purchases.</p>
        </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="mt-4">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
