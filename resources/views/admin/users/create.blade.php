<x-dashboard-layout
    role="Admin"
    title="Add User"
    standfirst="A new account for a counter. The role decides which desk this person can open."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                @include('admin.users._form-fields', ['user' => null])

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <button type="submit" class="kc-btn kc-btn--primary"
                            data-loading-label="Creating account">
                        Create user
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="kc-btn">Cancel</a>
                </div>
            </form>
        </div>

        <aside class="lg:col-span-4 lg:col-start-9">
            <p class="kc-label">What each role can do</p>
            <dl class="mt-4 space-y-5">
                <div>
                    <dt class="kc-label">Admin</dt>
                    <dd class="mt-1.5 text-[0.875rem] text-muted">
                        Products, accounts, and every order desk.
                    </dd>
                </div>
                <div>
                    <dt class="kc-label">Staff</dt>
                    <dd class="mt-1.5 text-[0.875rem] text-muted">
                        The order queue only. No access to products or accounts.
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
</x-dashboard-layout>
