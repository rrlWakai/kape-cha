<x-guest-layout>
<div class="mx-auto max-w-[90rem] px-5 md:px-10">
    <div class="mx-auto max-w-[42rem] py-12 md:py-20">
        @if ($order)
            {{-- A printed receipt: identity, then the ticket, then the total. --}}
            <header class="text-center">
                <p class="kc-wordmark kc-wordmark--large">KAPE-CHA</p>
                <p class="kc-meta mt-4">{{ config('kapecha.house.tagline') }}</p>
            </header>

            <div class="mt-12 border-y border-ink py-8 text-center">
                {{-- The literal heading below keeps the receipt wording the
                     order flow has always used. --}}
                <h1 class="kc-section">Order Confirmed</h1>
                <p class="mt-4 text-[0.875rem] tabular">
                    Order #{{ $order->order_number }}
                </p>
            </div>

            {{-- The state of the ticket, in words, immediately after placing it. --}}
            <div class="mt-8">
                <x-status-block :status="$order->status" />
            </div>

            <dl class="mt-8 space-y-4">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Customer</dt>
                    <dd class="text-[0.875rem]">{{ $order->customer_name }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Service</dt>
                    <dd class="text-[0.875rem]">{{ $order->service_type }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Placed</dt>
                    <dd class="text-[0.875rem] tabular">{{ $order->created_at?->format('j M Y, g:i A') }}</dd>
                </div>
            </dl>

            <div class="mt-8 border-t border-line">
                <p class="kc-label mt-8">Items</p>

                <div class="mt-2">
                    @foreach ($order->items as $line)
                        <div class="border-b border-line-soft py-4">
                            <div class="flex items-baseline justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="kc-line__name">{{ $line->product?->product_name ?? 'Removed dish' }}</p>
                                    <p class="kc-meta mt-1 tabular">
                                        @money($line->unit_price) &times; {{ $line->quantity }}
                                    </p>
                                </div>
                                <p class="kc-line__price shrink-0 tabular">@money($line->subtotal)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex items-baseline justify-between gap-4 border-t border-ink pt-5">
                <p class="kc-label">Total</p>
                <p class="kc-total tabular">@money($order->total_amount)</p>
            </div>

            <div class="mt-10 text-center">
                <p class="kc-subsection">Thank you for choosing Kape-Cha.</p>
                <p class="kc-prose mx-auto mt-4">{{ config('kapecha.house.hours_note') }}</p>
            </div>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:justify-center">
                {{-- The order is already placed, so the only honest next step
                     is a new one. Editing happens on the tray, not here. --}}
                <a href="{{ route('menu') }}" class="kc-btn kc-btn--primary">Order something else</a>
                <a href="{{ route('home') }}" class="kc-btn">Back to the menu</a>
            </div>
        @else
            <div class="text-center">
                <h1 class="kc-section">No recent order</h1>
                <p class="kc-prose mx-auto mt-6">
                    There is no order on this session. Start one from the menu and it will print here.
                </p>
                <a href="{{ route('menu') }}" class="kc-btn kc-btn--primary mt-8">Browse the menu</a>
            </div>
        @endif
    </div>
</div>
</x-guest-layout>
