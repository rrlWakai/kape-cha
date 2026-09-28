<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function index(): View
    {
        // Phase 1: read-only summaries from seeded/structural tables.
        $stats = [
            'pending' => Order::where('status', 'Pending')->count(),
            'processing' => Order::where('status', 'Processing')->count(),
            'completed' => Order::where('status', 'Completed')->count(),
            'products' => Product::count(),
            'low_stock' => Product::where('quantity', '<=', 5)->count(),
        ];

        $recentOrders = Order::latest('created_at')->take(5)->get();

        return view('staff.dashboard', compact('stats', 'recentOrders'));
    }
}
