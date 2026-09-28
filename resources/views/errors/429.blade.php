@include('errors.layout', [
    'code' => '429',
    'heading' => 'Too Many Requests',
    'body' => 'That was a lot of requests in a short time. Please wait a moment before trying again.',
    'note' => 'This is a temporary limit, not a problem with your order.',
    'primary' => route('menu'),
    'primaryLabel' => 'Wait and continue',
])
