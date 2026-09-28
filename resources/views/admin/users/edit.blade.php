<x-dashboard-layout
    role="Admin"
    :title="$user->username"
    standfirst="Change who this person is and what they can open. The password is only replaced if you type a new one."
>
    <x-slot:nav>
        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        <x-nav-link href="{{ route('admin.products.index') }}" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
        <x-nav-link href="{{ route('admin.orders.index') }}" :active="request()->routeIs('admin.orders.*')">Orders</x-nav-link>
    </x-slot:nav>

    <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                @include('admin.users._form-fields', ['user' => $user])

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <button type="submit" class="kc-btn kc-btn--primary"
                            data-loading-label="Updating account">
                        Update user
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="kc-btn">Cancel</a>
                </div>
            </form>

            {{-- Deleting an account that has processed tickets would erase part
                 of the order history, so the controller refuses it in plain
                 words. The dialog still explains that up front. --}}
            @if ($user->id !== auth()->id())
                <div class="mt-12 border-t border-line pt-8">
                    <p class="kc-label">Remove</p>
                    <p class="kc-prose mt-2">
                        This deletes the account. An account that has processed orders is kept
                        so the record stays intact.
                    </p>

                    <button type="button" class="kc-btn kc-btn--danger mt-5"
                            data-confirm-open="confirm-delete-{{ $user->id }}">
                        Delete user
                    </button>

                    <dialog class="kc-dialog" id="confirm-delete-{{ $user->id }}"
                            aria-labelledby="confirm-delete-title-{{ $user->id }}">
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                            @csrf
                            @method('DELETE')

                            <div class="kc-dialog__body">
                                <h2 class="kc-dialog__title" id="confirm-delete-title-{{ $user->id }}">
                                    Delete &ldquo;{{ $user->username }}&rdquo;?
                                </h2>
                                <p class="kc-dialog__note">
                                    This account will no longer be able to sign in. It cannot be
                                    undone, and it is refused if the account has processed orders.
                                </p>
                            </div>

                            <div class="kc-dialog__actions">
                                <button type="submit" class="kc-btn kc-btn--danger">Delete user</button>
                                <button type="button" class="kc-btn" data-confirm-cancel>Keep user</button>
                            </div>
                        </form>
                    </dialog>
                </div>
            @endif
        </div>

        <aside class="lg:col-span-4 lg:col-start-9">
            <p class="kc-label">On file</p>
            <dl class="mt-4 space-y-3">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Role</dt>
                    <dd class="text-[0.875rem]"><x-status-badge :status="$user->role" /></dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="kc-label">Orders processed</dt>
                    <dd class="text-[0.875rem] tabular">{{ \App\Models\Order::where('processed_by', $user->id)->count() }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</x-dashboard-layout>
