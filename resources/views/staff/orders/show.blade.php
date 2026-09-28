@php
    // Admin reuses these screens inside the /admin prefix, so both the process
    // form and the desk navigation must stay in whichever prefix the operator
    // is actually standing at.
    $prefix = request()->routeIs('admin.*') ? 'admin' : 'staff';
    $processRoute = route($prefix.'.orders.process', $order);
    $cancelRoute = route($prefix.'.orders.cancel', $order);
    $dashboardUrl = route($prefix.'.dashboard');
    $queueUrl = route($prefix.'.orders.index');
@endphp

<x-dashboard-layout
    :role="$prefix === 'admin' ? 'Admin' : 'Staff'"
    :title="$order->order_number"
    standfirst="The ticket as it stands. Processing runs one database transaction: lock, verify, deduct, commit."
>
    <x-slot:nav>
        <x-nav-link href="{{ $dashboardUrl }}" :active="request()->routeIs($prefix.'.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ $queueUrl }}" :active="request()->routeIs($prefix.'.orders.*')">Orders</x-nav-link>
        <x-nav-link href="{{ route('menu') }}">Menu</x-nav-link>
    </x-slot:nav>

    <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
        {{-- The ticket itself. --}}
        <div class="lg:col-span-7">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-3 border-b border-ink pb-5">
                <h2 class="kc-subsection">Order #{{ $order->order_number }}</h2>
                <x-status-badge :status="$order->status" />
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-x-8 gap-y-5">
                <div>
                    <dt class="kc-label">Customer</dt>
                    <dd class="mt-1.5 text-[0.875rem]">{{ $order->customer_name ?: 'Anonymous Guest' }}</dd>
                </div>
                <div>
                    <dt class="kc-label">Service</dt>
                    <dd class="mt-1.5 text-[0.875rem]">{{ $order->service_type }}</dd>
                </div>
                <div>
                    <dt class="kc-label">Date / Time</dt>
                    <dd class="mt-1.5 text-[0.875rem] tabular">{{ $order->created_at?->format('j M Y, g:i A') }}</dd>
                </div>
                <div>
                    <dt class="kc-label">Processed By</dt>
                    <dd class="mt-1.5 text-[0.875rem]">
                        {{ $order->processedBy?->username ?? 'Not yet processed' }}
                    </dd>
                </div>
            </dl>

            <div class="mt-8">
                <p class="kc-label">Items</p>

                <table class="kc-table mt-4">
                    <caption class="sr-only">Every line on ticket {{ $order->order_number }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col" class="text-right">Qty</th>
                            <th scope="col" class="text-right">Unit</th>
                            <th scope="col" class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $line)
                            <tr>
                                <td data-label="Item">{{ $line->product?->product_name ?? 'Removed dish' }}</td>
                                <td data-label="Qty" class="text-right tabular">{{ $line->quantity }}</td>
                                <td data-label="Unit" class="text-right tabular">@money($line->unit_price)</td>
                                <td data-label="Subtotal" class="text-right tabular">@money($line->subtotal)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-6 flex items-baseline justify-between gap-4 border-t border-ink pt-5">
                    <p class="kc-label">Total</p>
                    <p class="kc-total tabular">@money($order->total_amount)</p>
                </div>
            </div>
        </div>

        {{-- The counter's actions and the transaction demonstrator. --}}
        <div class="lg:col-span-5">
            <div class="kc-sheet p-6 md:p-7">
                <p class="kc-label">Current Status</p>
                <p class="mt-2"><x-status-badge :status="$order->status" /></p>

                <div class="mt-4">
                    <x-status-block :status="$order->status" />
                </div>

                @if ($order->status === 'Pending')
                    <div class="mt-6 space-y-3">
                        <form method="POST" action="{{ $processRoute }}">
                            @csrf
                            <button type="submit" class="kc-btn kc-btn--primary kc-btn--block kc-btn--lg"
                                    data-loading-label="Processing order">
                                Process order
                            </button>
                        </form>

                        <p class="kc-hint">
                            Marks the ticket Completed and deducts every line from stock in one transaction.
                        </p>

                        {{-- Withdrawal is destructive: it is styled as such and
                             must be confirmed in a dialog that names the ticket. --}}
                        <button type="button" class="kc-btn kc-btn--danger kc-btn--block"
                                data-confirm-open="confirm-cancel-{{ $order->order_id }}">
                            Cancel order
                        </button>

                        <dialog class="kc-dialog" id="confirm-cancel-{{ $order->order_id }}"
                                aria-labelledby="confirm-cancel-title-{{ $order->order_id }}">
                            <form method="POST" action="{{ $cancelRoute }}">
                                @csrf

                            <div class="kc-dialog__body">
                                <h2 class="kc-dialog__title" id="confirm-cancel-title-{{ $order->order_id }}">
                                    Cancel this order?
                                </h2>
                                <p class="kc-dialog__note">
                                    This order will be marked as cancelled. No stock is deducted, and
                                    the ticket can no longer be processed.
                                </p>
                            </div>

                            <div class="kc-dialog__actions">
                                <button type="submit" class="kc-btn kc-btn--danger">
                                    Cancel order
                                </button>
                                <button type="button" class="kc-btn" data-confirm-cancel>Keep order</button>
                            </div>

                            </form>
                        </dialog>

                        <form method="POST" action="{{ $processRoute }}" class="pt-2">
                            @csrf
                            <input type="hidden" name="simulate_failure" value="1">
                            <button type="submit" class="kc-btn kc-btn--ghost kc-btn--block"
                                    data-loading-label="Running rollback">
                                Simulate failure
                            </button>
                        </form>

                        <p class="kc-meta">
                            <span class="block">Scenario A &middot; Commit.</span>
                            <span class="mt-1 block">
                                Scenario B &middot; Fail after the row locks are held, to prove that ROLLBACK
                                restores every quantity it touched.
                            </span>
                        </p>
                    </div>
                @else
                    <p class="kc-meta mt-6 border-t border-line pt-5">
                        This ticket is {{ strtolower($order->status) }} and no longer accepts a transaction.
                    </p>
                @endif
            </div>

            <div class="mt-8">
                <p class="kc-label">ACID Transaction Demonstrator</p>
                <p class="kc-meta mt-2">
                    Lock, verify, deduct, commit — or roll the whole ticket back.
                </p>

                <div class="mt-5">
                    <x-tx-terminal :log="session('tx_log')" :outcome="session('tx_outcome')" />
                </div>
            </div>

            <div class="mt-8">
                <x-acid-badges />
            </div>
        </div>
    </div>
</x-dashboard-layout>
