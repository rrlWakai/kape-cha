@props(['title' => null])

@php
    $house = config('kapecha.house');
    $cartCount = app(\App\Services\CartService::class)->count();
    $user = auth()->user();
@endphp

<x-app-layout :title="$title">
    <div class="flex min-h-screen flex-col">
        {{-- A small, quiet header: the house on the left, three words on the right. --}}
        <header class="border-b border-line bg-paper">
            <div class="mx-auto flex max-w-[90rem] items-center justify-between gap-6 px-5 py-5 md:px-10 md:py-6">
                <a href="{{ route('home') }}" class="kc-wordmark shrink-0">KAPE-CHA</a>

                <nav class="flex items-baseline gap-6 md:gap-9" aria-label="Customer">
                    <a href="{{ route('menu') }}" class="kc-nav" @if (request()->routeIs('menu', 'home')) aria-current="page" @endif>
                        Menu
                    </a>

                    <a href="{{ route('cart.index') }}" class="kc-nav" @if (request()->routeIs('cart.*')) aria-current="page" @endif>
                        My Cart
                        @if ($cartCount > 0)
                            <span class="ml-1 text-ink tabular">{{ $cartCount }}</span>
                        @endif
                    </a>

                    @if ($user)
                        <a href="{{ request()->routeIs('admin.*') ? route('admin.dashboard') : route('staff.dashboard') }}"
                           class="kc-nav hidden sm:inline-flex">
                            {{ $user->role }} Desk
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="kc-nav hidden sm:inline-flex">Staff</a>
                    @endif
                </nav>
            </div>
        </header>

        <main class="flex-1">
            @include('components.partials.notices')

            {{ $slot }}
        </main>

        {{-- Colophon: the same printed facts as the masthead, closing the sheet. --}}
        <footer class="mt-24 border-t border-line">
            <div class="mx-auto max-w-[90rem] px-5 py-12 md:px-10 md:py-16">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="kc-wordmark">KAPE-CHA</p>
                        <p class="kc-meta mt-4">{{ $house['tagline'] }}</p>
                    </div>

                    <div>
                        <p class="kc-label">Opening Hours</p>
                        <dl class="mt-4 space-y-2">
                            @foreach ($house['hours'] as $days => $time)
                                <div class="flex justify-between gap-4">
                                    <dt class="kc-meta">{{ $days }}</dt>
                                    <dd class="kc-meta tabular">{{ $time }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div>
                        <p class="kc-label">Visit</p>
                        <p class="kc-meta mt-4 leading-relaxed">
                            {{ $house['address'] }}<br>
                            {{ $house['contact'] }}
                        </p>
                    </div>

                    <div>
                        <p class="kc-label">Elsewhere</p>
                        <ul class="mt-4 space-y-2">
                            <li><a href="{{ route('menu') }}" class="kc-link">Menu</a></li>
                            <li><a href="{{ route('cart.index') }}" class="kc-link">My Cart</a></li>
                            <li><a href="{{ route('login') }}" class="kc-link">Staff Portal</a></li>
                        </ul>
                    </div>
                </div>

                <div class="kc-colophon mt-14 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ now()->year }} {{ $house['name'] }}. {{ $house['established'] }}.</p>
                    <p>{{ $house['promise'] }}</p>
                </div>
            </div>
        </footer>
    </div>
</x-app-layout>
