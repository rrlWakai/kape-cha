<x-dashboard-layout
    role="Admin"
    title="Executive Management"
    standfirst="The back office of the same café: what came in today, and what is running low."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    {{-- Today: four numbers on a rule. No KPI cards, no charts. --}}
    <section>
        <p class="kc-label">Today</p>

        <div class="mt-5 grid grid-cols-2 gap-x-8 gap-y-8 border-t border-line pt-6 md:grid-cols-4">
            <x-figure label="Orders" :value="$stats['orders']" note="All tickets on file" />
            <x-figure label="Completed" :value="$stats['completed']" note="Poured and handed over" />
            <x-figure label="Pending" :value="$stats['pending']" note="Still on the board" />
            <x-figure label="Sales" :value="$stats['inventory_value']" money note="Stock value at menu price" />
        </div>
    </section>

    <div class="mt-16 grid gap-14 lg:grid-cols-12 lg:gap-16">
        {{-- Recent orders. --}}
        <section class="lg:col-span-7">
            <div class="flex items-baseline justify-between gap-4 border-b border-ink pb-4">
                <h2 class="kc-subsection">Recent Orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="kc-action">All orders</a>
            </div>

            @forelse ($recentOrders as $order)
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-line-soft py-4">
                    <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                        <a href="{{ route('admin.orders.show', $order) }}" class="kc-line__name tabular">{{ $order->order_number }}</a>
                        <span class="kc-meta">{{ $order->customer_name ?: 'Anonymous Guest' }}</span>
                        <span class="kc-meta tabular">{{ $order->created_at?->format('j M, g:i A') }}</span>
                    </div>

                    <div class="flex items-baseline gap-5">
                        <x-status-badge :status="$order->status" />
                        <span class="kc-line__price tabular">@money($order->total_amount)</span>
                    </div>
                </div>
            @empty
                <p class="kc-prose py-10">No orders on file yet.</p>
            @endforelse
        </section>

        {{-- Low stock: the list the kitchen actually acts on. --}}
        <section class="lg:col-span-5">
            <div class="flex items-baseline justify-between gap-4 border-b border-ink pb-4">
                <h2 class="kc-subsection">Low Stock</h2>
                <a href="{{ route('admin.products.index') }}" class="kc-action">Inventory</a>
            </div>

            @forelse ($lowStockProducts as $product)
                <div class="flex items-baseline justify-between gap-4 border-b border-line-soft py-4">
                    <div class="min-w-0">
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-[0.875rem] font-medium">{{ $product->product_name }}</a>
                        <p class="kc-meta mt-0.5">{{ $product->category }}</p>
                    </div>

                    <p class="kc-meta shrink-0 tabular">{{ $product->availability() }}</p>
                </div>
            @empty
                <p class="kc-prose py-10">Every item is well stocked.</p>
            @endforelse

            <div class="mt-8 flex flex-wrap gap-x-10 gap-y-4 border-t border-line pt-6">
                <p class="kc-meta">
                    <span class="block text-ink tabular">{{ $stats['products'] }}</span>
                    menu items
                </p>
                <p class="kc-meta">
                    <span class="block text-ink tabular">{{ $stats['low_stock'] }}</span>
                    low in stock
                </p>
                <p class="kc-meta">
                    <span class="block text-ink tabular">{{ $stats['users'] }}</span>
                    accounts
                </p>
            </div>
        </section>
    </div>
</x-dashboard-layout>
