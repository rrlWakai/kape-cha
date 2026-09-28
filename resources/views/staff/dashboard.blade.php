<x-dashboard-layout
    role="Staff"
    title="POS Dispatch"
    standfirst="The counter's board. Orders arrive here as tickets and leave as completed pours."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('staff.dashboard') }}" :active="request()->routeIs('staff.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('staff.orders.index') }}" :active="request()->routeIs('staff.orders.*')">Orders</x-nav-link>
        <x-nav-link href="{{ route('menu') }}">Menu</x-nav-link>
    </x-slot:nav>

    {{-- Today at the counter, set on a rule rather than in cards. --}}
    <section>
        <p class="kc-label">Today</p>

        <div class="mt-5 grid grid-cols-2 gap-x-8 gap-y-8 border-t border-line pt-6 md:grid-cols-4">
            <x-figure label="Pending" :value="$stats['pending']" note="Waiting to be pulled" />
            <x-figure label="Processing" :value="$stats['processing']" note="On the bar now" />
            <x-figure label="Completed" :value="$stats['completed']" note="Handed over" />
            <x-figure label="Menu Items" :value="$stats['products']" :note="$stats['low_stock'].' low in stock'" />
        </div>
    </section>

    {{-- The last tickets the counter saw. --}}
    <section class="mt-14">
        <div class="flex items-baseline justify-between gap-4 border-b border-ink pb-4">
            <h2 class="kc-subsection">Recent Orders</h2>
            <a href="{{ route('staff.orders.index') }}" class="kc-action">All orders</a>
        </div>

        @forelse ($recentOrders as $order)
            <div class="border-b border-line-soft py-5">
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                        <a href="{{ route('staff.orders.show', $order) }}" class="kc-line__name tabular">{{ $order->order_number }}</a>
                        <span class="kc-meta">{{ $order->customer_name ?: 'Anonymous Guest' }}</span>
                        <span class="kc-meta">{{ $order->service_type }}</span>
                    </div>

                    <div class="flex items-baseline gap-5">
                        <x-status-badge :status="$order->status" />
                        <span class="kc-line__price tabular">@money($order->total_amount)</span>
                    </div>
                </div>
            </div>
        @empty
            <p class="kc-prose py-10">No orders on the board yet.</p>
        @endforelse
    </section>
</x-dashboard-layout>
