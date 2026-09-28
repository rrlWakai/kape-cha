<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ValidationMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Creating a counter account.
 *
 * The confirmation field is checked against the real password rather than
 * only for presence, so "Passwords do not match." is reported even when both
 * boxes are filled in.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required', 'string', 'max:50',
                'regex:/^[A-Za-z0-9._\-]+$/',
                Rule::unique('users', 'username'),
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return array_merge(
            ValidationMessages::accountUsername(),
            ValidationMessages::accountRole(),
            ValidationMessages::accountPassword(),
        );
    }

    public function attributes(): array
    {
        return [
            'username' => 'username',
            'role' => 'role',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => trim($this->input('username'))]);
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Belt and braces: if for any reason the confirmation rule did
                // not fire, still refuse silently mismatched passwords.
                if ($this->filled('password_confirmation')
                    && $this->input('password') !== $this->input('password_confirmation')) {
                    $validator->errors()->add('password_confirmation', 'Passwords do not match.');
                }
            },
        ];
    }
}
