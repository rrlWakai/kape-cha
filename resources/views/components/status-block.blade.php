@props(['status'])

@php
    /**
     * The word, then what it means.
     *
     * A bare badge asks the reader to decode a colour. This says
     * "PENDING" and then, in ordinary words, what happens next - so the
     * state is obvious whether or not the tint is perceived.
     */
    $detail = match (strtolower((string) $status)) {
        'pending' => 'Your order is waiting to be processed.',
        'processing' => 'Your order is currently being prepared.',
        'completed' => 'Your order has been completed.',
        'cancelled' => 'This order has been cancelled.',
        default => 'The current state of this order.',
    };

    $word = ucfirst(strtolower((string) $status));
@endphp

<div {{ $attributes->merge(['class' => 'kc-status-block kc-status-block--'.strtolower($word)]) }}>
    <p class="kc-label">Order Status</p>
    <p class="kc-status-block__word mt-2">{{ $word }}</p>
    <p class="kc-status-block__note">{{ $detail }}</p>
</div>
