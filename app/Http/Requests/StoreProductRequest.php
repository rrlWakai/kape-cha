<?php

namespace App\Http\Requests;

use App\Support\ValidationMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating a menu item.
 *
 * Price is validated as a number with `min:0` rather than `gt:0` so a
 * deliberately free item (a taster, a water) is a legal thing to enter
 * rather than a value the form refuses while leaving no way to express it.
 * Zero is therefore valid and only a negative price is rejected.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:100', 'unique:products,product_name'],
            'category' => ['required', 'string', Rule::in(config('kapecha.sections'))],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ValidationMessages::product();
    }

    /**
     * Trim before validating, so a name typed as "   " is treated as empty
     * rather than stored as invisible characters.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('product_name'))) {
            $this->merge(['product_name' => trim($this->input('product_name'))]);
        }

        if (is_string($this->input('description'))) {
            $this->merge(['description' => trim($this->input('description'))]);
        }
    }
}
