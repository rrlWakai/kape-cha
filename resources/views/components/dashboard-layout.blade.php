@props(['role' => 'Staff', 'title' => null, 'standfirst' => null])

@php
    $user = auth()->user();
    $home = request()->routeIs('admin.*') ? route('admin.dashboard') : route('staff.dashboard');
@endphp

<x-app-layout :title="$title ? $title.' — KAPE-CHA '.$role : 'KAPE-CHA '.$role.' Desk'">
    <div class="flex min-h-screen flex-col">
        {{-- The back office shares the café's header. Same paper, same wordmark,
             one extra word for the desk. --}}
        <header class="border-b border-line bg-paper">
            <div class="mx-auto max-w-[90rem] px-5 md:px-10">
                <div class="flex items-center justify-between gap-6 py-5">
                    <a href="{{ $home }}" class="flex items-baseline gap-3">
                        <span class="kc-wordmark">KAPE-CHA</span>
                        <span class="kc-label hidden sm:inline">{{ $role }}</span>
                    </a>

                    <div class="flex items-baseline gap-5 md:gap-7">
                        <span class="kc-meta hidden tabular lg:inline">{{ now()->format('D, j M Y') }}</span>

                        @if ($user)
                            <span class="kc-meta">{{ $user->username }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="kc-action">Sign out</button>
                            </form>
                        @endif
                    </div>
                </div>

                <nav class="flex items-end gap-6 overflow-x-auto pb-px md:gap-9" aria-label="{{ $role }} sections">
                    {{ $nav }}
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[90rem] flex-1 px-5 py-10 md:px-10 md:py-14">
            @if ($title)
                <div class="mb-10 flex flex-col gap-4 border-b border-ink pb-6 md:mb-12 md:flex-row md:items-end md:justify-between">
                    <h1 class="kc-section">{{ $title }}</h1>

                    @if ($standfirst)
                        <p class="kc-prose text-[0.8125rem] md:max-w-xs md:text-right">{{ $standfirst }}</p>
                    @endif
                </div>
            @endif

            @include('components.partials.notices', ['wrap' => ''])

            {{ $slot }}
        </main>

        <footer class="border-t border-line">
            <div class="mx-auto flex max-w-[90rem] flex-col gap-2 px-5 py-6 md:flex-row md:items-center md:justify-between md:px-10">
                <p class="kc-meta">KAPE-CHA &middot; {{ config('kapecha.house.tagline') }}</p>
                <a href="{{ route('menu') }}" class="kc-link">View the public menu</a>
            </div>
        </footer>
    </div>
</x-app-layout>
