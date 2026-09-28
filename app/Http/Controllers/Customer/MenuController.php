<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(Request $request): View
    {
        // The board only prints the sections it actually has, so a deep link
        // to ?category=Food works the day the kitchen adds food.
        $categories = Product::query()
            ->select('category')
            ->distinct()
            ->pluck('category');

        $requested = $request->query('category');
        $category = in_array($requested, $categories->all(), true) ? $requested : null;

        $products = Product::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('product_name')
            ->get();

        return view('customer.menu', [
            'products' => $products,
            'activeCategory' => $category,
            'cartCount' => $this->cart->count(),
            'cartTotal' => $this->cart->total(),
            // What is genuinely still addable, so the stepper's upper bound
            // agrees with the server instead of contradicting it.
            'remaining' => $products
                ->mapWithKeys(fn (Product $p) => [$p->product_id => $this->cart->remainingFor($p)])
                ->all(),
        ]);
    }
}
