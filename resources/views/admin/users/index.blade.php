<x-dashboard-layout
    role="Admin"
    title="Users"
    standfirst="Accounts with access to a KAPE-CHA desk. Roles decide which counter each person can stand at."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div>
        <table class="kc-table kc-table--stack">
            <caption class="sr-only">Every account with access to a KAPE-CHA desk</caption>
            <thead>
                <tr>
                    <th scope="col">Username</th>
                    <th scope="col">Role</th>
                    <th scope="col">Information</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td data-label="Username" class="font-medium">{{ $user->username }}</td>

                        <td data-label="Role">
                            <x-status-badge :status="$user->role" />
                        </td>

                        <td data-label="Information" class="text-muted">
                            {{ $user->role === 'Admin' ? 'Full access: products, users, and every desk.' : 'Counter access: orders only.' }}
                        </td>

                        <td data-label="Actions" class="text-right">
                            @if ($user->id === auth()->id())
                                <span class="kc-meta">Signed in</span>
                            @else
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="kc-btn kc-btn--sm">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-muted">No accounts on file.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-10">
        <a href="{{ route('admin.users.create') }}" class="kc-btn kc-btn--primary">
            Add user
        </a>
    </div>

    @if ($users->hasPages())
        <div class="mt-10">
            {{ $users->links('pagination.folio') }}
        </div>
    @endif
</x-dashboard-layout>
