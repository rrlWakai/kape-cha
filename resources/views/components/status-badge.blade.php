@props(['status'])

@php
    // Ticket states and desk roles share one marked-word treatment so the
    // back office never grows a second visual language.
    $tone = match (strtolower((string) $status)) {
        'pending' => 'pending',
        'processing' => 'processing',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'admin' => 'admin',
        'staff' => 'staff',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => 'kc-status kc-status--'.$tone]) }}>{{ $status }}</span>
