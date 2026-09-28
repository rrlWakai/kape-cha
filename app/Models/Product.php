<?php

namespace App\Models;

use App\Support\Photography;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_name',
        'category',
        'price',
        'quantity',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function isAvailable(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * The anchor for this dish on the printed menu.
     */
    public function slug(): string
    {
        return 'dish-'.$this->product_id;
    }

    /**
     * The photograph for this dish, at the size the layout needs.
     */
    public function photo(int $width = 1200, int $height = 0): string
    {
        return Photography::url($this->product_name, $width, $height, $this->category);
    }

    public function photoAlt(): string
    {
        return Photography::alt($this->product_name, $this->category);
    }

    /**
     * The composition line printed beneath the dish name.
     */
    public function notes(): string
    {
        $notes = config('kapecha.notes');

        if (isset($notes[$this->product_name])) {
            return $notes[$this->product_name];
        }

        $category = config('kapecha.category_notes');

        return $category[$this->category] ?? config('kapecha.default_note');
    }

    /**
     * How this dish reads on the stock line: a full pour, a short one, or an
     * empty case.
     */
    public function availability(): string
    {
        if ($this->quantity <= 0) {
            return 'Sold out';
        }

        if ($this->quantity <= 5) {
            return 'Only '.$this->quantity.' left';
        }

        return $this->quantity.' in stock';
    }
}
