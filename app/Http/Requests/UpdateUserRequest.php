<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Editing a counter account.
 *
 * The password is optional here: an administrator changing someone's role
 * should not be forced to invent a new password, and a blank field means
 * "leave the existing one alone" rather than "clear it".
 */
class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        $rules = [
            'username' => [
                'required', 'string', 'max:50',
                'regex:/^[A-Za-z0-9._\-]+$/',
                Rule::unique('users', 'username')
                    ->ignore($this->route('user')->id, 'id'),
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
        ];

        // Only validate a password when one was actually typed.
        if ($this->filled('password')) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        return $rules;
    }
}
