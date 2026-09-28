<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Editing a menu item.
 *
 * The only difference from creating one is the uniqueness check, which has
 * to ignore the row being edited — otherwise saving an unchanged product
 * would report itself as a duplicate of itself.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['product_name'] = [
            'required', 'string', 'max:100',
            Rule::unique('products', 'product_name')
                ->ignore($this->route('product')->product_id, 'product_id'),
        ];

        return $rules;
    }
}
