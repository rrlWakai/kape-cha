@php
    // A real URL, not javascript:history.back() — a javascript: href is
    // blocked by most content-security policies and is unfollowable by
    // anyone using the keyboard or a screen reader.
    $back = url()->previous() !== url()->current() ? url()->previous() : route('menu');
@endphp

@include('errors.layout', [
    'code' => '500',
    'heading' => 'Something Went Wrong',
    'body' => "We couldn't complete that request.",
    'note' => 'Nothing was changed. Please try again in a moment.',
    'primary' => $back,
    'primaryLabel' => 'Try again',
    'secondary' => route('menu'),
    'secondaryLabel' => 'Return to menu',
])
