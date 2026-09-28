<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('product_name')->paginate(10);

        $lowStockCount = Product::where('quantity', '>', 0)->where('quantity', '<=', 5)->count();
        $soldOutCount = Product::where('quantity', '<=', 0)->count();

        return view('admin.products.index', compact('products', 'lowStockCount', 'soldOutCount'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', ['product' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Product \"{$product->product_name}\" updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->product_name;
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Product \"{$name}\" deleted.");
    }
}
