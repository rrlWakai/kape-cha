<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('username')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'username' => $request->validated('username'),
            'role' => $request->validated('role'),
            // Hashed by the model's cast; the plaintext is never stored or
            // echoed back into a message.
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$user->username}\" created successfully.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = [
            'username' => $request->validated('username'),
            'role' => $request->validated('role'),
        ];

        // A blank password field means "leave it as it is", so that an admin
        // correcting a role is not forced to reset the person's password.
        if ($request->filled('password')) {
            $attributes['password'] = $request->validated('password');
        }

        // Removing the last administrator would lock everyone out of this
        // console, so it is refused with an explanation rather than allowed
        // to fail on a constraint.
        if ($user->isAdmin()
            && $attributes['role'] !== User::ROLE_ADMIN
            && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()
                ->withInput()
                ->with('error', 'This is the only Admin account. Promote another account to Admin before changing this one.');
        }

        $user->update($attributes);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$user->username}\" updated successfully.");
    }

    /**
     * Remove an account that has no work attached to it.
     *
     * Deleting somebody who appears on a processed ticket would erase part of
     * the order history, so that case is refused in plain words instead of
     * being left to a foreign key error.
     */
    public function destroy(User $user): RedirectResponse
    {
        $name = $user->username;

        $processedCount = DB::table('orders')
            ->where('processed_by', $user->id)
            ->count();

        if ($processedCount > 0) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', "\"{$name}\" has processed {$processedCount} order(s), so the account is kept for the record. Change the role instead of deleting it.");
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'This is the only Admin account and cannot be deleted.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$name}\" deleted.");
    }
}
