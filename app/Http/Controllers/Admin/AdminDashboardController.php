<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'products' => Product::count(),
            'orders' => Order::count(),
            'completed' => Order::where('status', 'Completed')->count(),
            'pending' => Order::where('status', 'Pending')->count(),
            'users' => User::count(),
            'low_stock' => Product::where('quantity', '<=', 5)->count(),
            'inventory_value' => Product::selectRaw('SUM(price * quantity) as v')->value('v') ?? 0,
        ];

        $recentOrders = Order::latest('created_at')->take(5)->get();
        $lowStockProducts = Product::orderBy('quantity')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts'));
    }
}
