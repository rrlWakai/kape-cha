<x-dashboard-layout
    role="Staff"
    title="Live Order Queue"
    standfirst="Every ticket the counter has open, newest first. Open one to pour it."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('staff.dashboard') }}" :active="request()->routeIs('staff.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('staff.orders.index') }}" :active="request()->routeIs('staff.orders.*')">Orders</x-nav-link>
        <x-nav-link href="{{ route('menu') }}">Menu</x-nav-link>
    </x-slot:nav>

    {{-- Status filter: a row of words with quiet counts, not tabs in boxes. --}}
    <nav class="flex flex-wrap items-baseline gap-x-6 gap-y-2 border-b border-line pb-4" aria-label="Filter orders by status">
        <a href="{{ route('staff.orders.index') }}"
           class="kc-nav {{ $status === 'all' ? 'is-active' : '' }}"
           @if ($status === 'all') aria-current="page" @endif>
            All <span class="ml-1 tabular">{{ $counts['all'] }}</span>
        </a>

        @foreach (['Pending', 'Processing', 'Completed', 'Cancelled'] as $option)
            <a href="{{ route('staff.orders.index', ['status' => $option]) }}"
               class="kc-nav {{ $status === $option ? 'is-active' : '' }}"
               @if ($status === $option) aria-current="page" @endif>
                {{ $option }} <span class="ml-1 tabular">{{ $counts[strtolower($option)] }}</span>
            </a>
        @endforeach
    </nav>

    @forelse ($orders as $order)
        {{-- A ticket: number, guest, service, the lines, the total, one action. --}}
        <article class="border-b border-line py-7">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-3">
                <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                    <h2 class="kc-line__name tabular">{{ $order->order_number }}</h2>
                    <x-status-badge :status="$order->status" />
                </div>

                <p class="kc-meta tabular">
                    {{ $order->created_at?->format('j M, g:i A') }}
                    @if ($order->processedBy)
                        &middot; by {{ $order->processedBy->username }}
                    @endif
                </p>
            </div>

            <div class="mt-4 grid gap-x-8 gap-y-4 md:grid-cols-12">
                <div class="md:col-span-4">
                    <p class="kc-label">Customer</p>
                    <p class="mt-1.5 text-[0.875rem]">{{ $order->customer_name ?: 'Anonymous Guest' }}</p>
                    <p class="kc-meta mt-1">{{ $order->service_type }}</p>
                </div>

                <div class="md:col-span-5">
                    <p class="kc-label">Items</p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach ($order->items as $line)
                            <li class="text-[0.875rem]">
                                {{ $line->product?->product_name ?? 'Removed dish' }}
                                <span class="kc-meta">&times; {{ $line->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <p class="kc-label">Total</p>
                    <p class="kc-line__price mt-1.5 tabular">@money($order->total_amount)</p>
                </div>
            </div>

            <div class="mt-5">
                <a href="{{ route('staff.orders.show', $order) }}" class="kc-btn kc-btn--quiet">View order</a>
            </div>
        </article>
    @empty
        <p class="kc-prose py-16 text-center">No orders match this filter.</p>
    @endforelse

    @if ($orders->hasPages())
        <div class="mt-12">
            {{ $orders->links('pagination.folio') }}
        </div>
    @endif
</x-dashboard-layout>
