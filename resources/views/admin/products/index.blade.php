@php
    use Illuminate\Support\Str;
@endphp

<x-dashboard-layout
    role="Admin"
    title="Products / Inventory"
    standfirst="The catalogue as printed on the board — priced, stocked, and in the case right now."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <p class="kc-meta">
            {{ $products->total() }} {{ Str::plural('item', $products->total()) }} on the menu
            @if ($lowStockCount > 0)
                &middot; <span class="text-pending">{{ $lowStockCount }} low</span>
            @endif
            @if ($soldOutCount > 0)
                &middot; <span class="text-cancelled">{{ $soldOutCount }} sold out</span>
            @endif
        </p>

        <a href="{{ route('admin.products.create') }}" class="kc-btn kc-btn--primary">Add product</a>
    </div>

    <div class="mt-8">
        <table class="kc-table kc-table--stack">
            <caption class="sr-only">Every product on the menu with its price and stock level</caption>
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Category</th>
                    <th scope="col" class="text-right">Price</th>
                    <th scope="col" class="text-right">Stock</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td data-label="Product">
                            <span class="font-medium">{{ $product->product_name }}</span>
                            @if ($product->description)
                                <span class="kc-meta mt-1 block max-w-xs">{{ Str::limit($product->description, 70) }}</span>
                            @endif
                        </td>
                        <td data-label="Category" class="text-muted">{{ $product->category }}</td>
                        <td data-label="Price" class="text-right tabular">@money($product->price)</td>
                        <td data-label="Stock" class="text-right tabular">
                            <span @class([
                                'text-pending' => $product->quantity > 0 && $product->quantity <= 5,
                                'text-cancelled' => $product->quantity <= 0,
                            ])>{{ $product->quantity }}</span>
                        </td>
                        <td data-label="Actions" class="text-right">
                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <a href="{{ route('admin.products.edit', $product) }}" class="kc-btn kc-btn--sm">Edit</a>

                                {{-- Deleting takes a line off the board for good, so
                                     it is a real button in the destructive colour
                                     and it asks first. The submit button lives
                                     inside the dialog, which means that with
                                     scripting off the action is simply
                                     unavailable rather than accidentally
                                     one tap away. --}}
                                <button type="button" class="kc-btn kc-btn--sm kc-btn--danger"
                                        data-confirm-open="confirm-delete-{{ $product->product_id }}">
                                    Delete
                                </button>
                            </div>

                            <dialog class="kc-dialog" id="confirm-delete-{{ $product->product_id }}"
                                    aria-labelledby="confirm-delete-title-{{ $product->product_id }}">
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                                    @csrf
                                    @method('DELETE')

                                    <div class="kc-dialog__body">
                                        <h2 class="kc-dialog__title" id="confirm-delete-title-{{ $product->product_id }}">
                                            Delete &ldquo;{{ $product->product_name }}&rdquo;?
                                        </h2>
                                        <p class="kc-dialog__note">
                                            This takes the line off the menu permanently. It cannot be
                                            deleted while existing orders reference it; past orders
                                            keep their record either way.
                                        </p>
                                    </div>

                                    <div class="kc-dialog__actions">
                                        <button type="submit" class="kc-btn kc-btn--danger">
                                            Delete product
                                        </button>
                                        <button type="button" class="kc-btn" data-confirm-cancel>Cancel</button>
                                    </div>
                                </form>
                            </dialog>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-muted">The menu is empty.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="mt-10">
            {{ $products->links('pagination.folio') }}
        </div>
    @endif
</x-dashboard-layout>
