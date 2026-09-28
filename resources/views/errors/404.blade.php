@include('errors.layout', [
    'code' => '404',
    'heading' => 'Page Not Found',
    'body' => "The page you're looking for doesn't exist. It may have been moved, or the link may be mistyped.",
    'primary' => route('menu'),
    'primaryLabel' => 'Return to menu',
    'secondary' => auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('staff.dashboard')) : null,
    'secondaryLabel' => 'Your desk',
])
