@include('errors.layout', [
    'code' => '403',
    'heading' => 'Access Denied',
    'body' => "You don't have permission to access this page.",
    'note' => isset($requiredRole)
        ? 'This desk is limited to '.$requiredRole.' accounts. Ask an administrator if you need this page.'
        : 'Your account cannot open this page. Ask an administrator if you need access.',
    'primary' => auth()->check()
        ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('staff.dashboard'))
        : route('login'),
    'primaryLabel' => auth()->check() ? 'Go to your desk' : 'Sign in',
    'secondary' => route('menu'),
    'secondaryLabel' => 'Return to menu',
])
