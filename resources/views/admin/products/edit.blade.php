<x-dashboard-layout
    role="Admin"
    :title="$product->product_name"
    standfirst="Edit the line as it appears on the printed menu and in the case."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('admin.products.update', $product) }}">
                @csrf
                @method('PUT')

                @include('admin.products._form-fields', ['product' => $product])

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <button type="submit" class="kc-btn kc-btn--primary"
                            data-loading-label="Updating product">
                        Update product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="kc-btn">Cancel</a>
                </div>
            </form>

            {{-- Removal is separated from saving on purpose: one primary
                 action per screen, and the destructive one is unmistakable. --}}
            <div class="mt-12 border-t border-line pt-8">
                <p class="kc-label">Remove</p>
                <p class="kc-prose mt-2">
                    This takes the line off the board permanently. Past orders keep their record.
                </p>

                <button type="button" class="kc-btn kc-btn--danger mt-5"
                        data-confirm-open="confirm-delete-{{ $product->product_id }}">
                    Delete product
                </button>

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
                                This takes the line off the menu permanently. It cannot be deleted while
                                existing orders reference it.
                            </p>
                        </div>

                        <div class="kc-dialog__actions">
                            <button type="submit" class="kc-btn kc-btn--danger">Delete product</button>
                            <button type="button" class="kc-btn" data-confirm-cancel>Cancel</button>
                        </div>
                    </form>
                </dialog>
            </div>
        </div>

        {{-- The current plate, so the editor can see what the customer sees. --}}
        <aside class="lg:col-span-4 lg:col-start-9">
            <p class="kc-label">Current Plate</p>

            <div class="kc-photo mt-4 aspect-[4/5]">
                <img src="{{ $product->photo(600, 750) }}" alt="{{ $product->photoAlt() }}"
                     width="600" height="750" loading="lazy" decoding="async">
            </div>

            <dl class="mt-6 space-y-3">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Price</dt>
                    <dd class="text-[0.875rem] tabular">@money($product->price)</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Stock</dt>
                    <dd class="text-[0.875rem] tabular">{{ $product->quantity }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Status</dt>
                    <dd class="text-[0.875rem]">{{ $product->availability() }}</dd>
                </div>
            </dl>

            <a href="{{ route('menu') }}#{{ $product->slug() }}" class="kc-link mt-6 inline-block">View on the menu</a>
        </aside>
    </div>
</x-dashboard-layout>
