<x-dashboard-layout
    role="Admin"
    title="Add Product"
    standfirst="A new line on the printed menu. It appears on the board as soon as it is saved."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('admin.products.store') }}">
                @csrf

                @include('admin.products._form-fields', ['product' => null])

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <button type="submit" class="kc-btn kc-btn--primary"
                            data-loading-label="Saving product">
                        Save product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="kc-btn">Cancel</a>
                </div>
            </form>
        </div>

        <aside class="lg:col-span-4 lg:col-start-9">
            <p class="kc-label">How it prints</p>
            <p class="kc-prose mt-3">
                Drinks appear in the photograph grid; food and dessert print as a compact
                text list. The composition line is set in <code class="text-[0.8125rem]">config/kapecha.php</code>.
            </p>
        </aside>
    </div>
</x-dashboard-layout>
