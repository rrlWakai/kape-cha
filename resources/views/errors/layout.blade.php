{{--
    The single frame every error page is printed inside.

    An error screen is still a Kape-Cha screen: the wordmark, the paper, the
    same type. It never shows a stack trace, an exception class, or an HTTP
    code as the headline — the code is a quiet fact in the corner and the
    heading says what a person can act on.
--}}
@props([
    'code' => '',
    'heading' => '',
    'body' => '',
    'note' => null,
    'primary' => null,
    'primaryLabel' => 'Go back',
    'secondary' => null,
    'secondaryLabel' => null,
])

@php
    // A signed-in operator always gets a way back to their own desk; a guest
    // gets the menu, which is the one page that always exists.
    $desk = auth()->check()
        ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('staff.dashboard'))
        : route('menu');
@endphp

<x-guest-layout :title="$heading ? $heading.' — KAPE-CHA' : 'KAPE-CHA'">
    <div class="flex min-h-[80vh] items-center justify-center px-5 py-20 md:py-28">
        <div class="w-full max-w-[32rem] text-center">

            {{-- The status is printed small, as a receipt detail. --}}
            @if ($code)
                <p class="kc-label tabular">{{ $code }}</p>
            @endif

            <h1 class="kc-section mt-6">{{ $heading }}</h1>

            @if ($body)
                <p class="kc-prose mx-auto mt-6 max-w-[26rem]">{{ $body }}</p>
            @endif

            @if ($note)
                <div class="mx-auto mt-8 max-w-sm text-left">
                    <div class="kc-msg kc-msg--info" role="status">
                        <span class="kc-msg__icon" aria-hidden="true">i</span>
                        <div class="min-w-0 flex-1">
                            <p class="kc-msg__body">{!! $note !!}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ $primary ?? $desk }}" class="kc-btn kc-btn--primary kc-btn--lg">
                    {{ $primaryLabel }}
                </a>

                @if ($secondary)
                    <a href="{{ $secondary }}" class="kc-btn kc-btn--lg">{{ $secondaryLabel }}</a>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
