@include('errors.layout', [
    'code' => '422',
    'heading' => 'Check Your Details',
    'body' => 'Some of the information sent with that request could not be accepted.',
    'note' => 'Go back to the form and correct the highlighted fields. Nothing was saved.',
    'primary' => url()->previous() !== url()->current() ? url()->previous() : route('menu'),
    'primaryLabel' => 'Go back',
    'secondary' => route('menu'),
    'secondaryLabel' => 'Return to menu',
])
