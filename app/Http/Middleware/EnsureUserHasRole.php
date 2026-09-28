<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role-based authorization middleware.
 *
 * Usage: ->middleware('role:Admin') or ->middleware('role:Staff')
 * Admin implicitly passes Staff checks (Admin may access Staff-level pages).
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            $allowed = $role === 'Staff'
                ? in_array($user->role, ['Staff', 'Admin'], true)
                : $user->role === $role;

            if ($allowed) {
                return $next($request);
            }
        }

        // The house 403 page, with the role that would be allowed through.
        return response()->view('errors.403', [
            'requiredRole' => implode(' / ', $roles),
        ], 403);
    }
}
