@include('errors.layout', [
    'code' => '419',
    'heading' => 'Session Expired',
    'body' => 'Your session has expired. This usually means the page was left open for a while.',
    'note' => 'Anything you had already saved is safe. Only the last form needs to be sent again.',
    'primary' => route('menu'),
    'primaryLabel' => 'Continue',
])
