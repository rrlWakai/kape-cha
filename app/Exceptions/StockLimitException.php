<?php

namespace App\Exceptions;

use App\Models\Product;
use App\Support\ValidationMessages;
use RuntimeException;

/**
 * Raised when a cart change would exceed what the counter actually has.
 *
 * A limit is never applied silently: the customer is told the real number and
 * the real reason, rather than having a button quietly stop responding.
 * Silently trimming the quantity would leave the receipt disagreeing with
 * what they asked for and give them no way to know.
 *
 * The exception carries two separate sentences because they do different
 * jobs. The headline says a limit was hit at all; the detail names the dish
 * and the count so the number can be corrected without guessing.
 */
class StockLimitException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct(sprintf(
            'Only %d %s %s currently available.',
            $available,
            $product->product_name,
            $available === 1 ? 'item is' : 'items are'
        ));
    }

    /** The headline, shown above the form. */
    public function headline(): string
    {
        return ValidationMessages::notEnoughStock();
    }

    /** The specific line, shown under the quantity that is too large. */
    public function detail(): string
    {
        return ValidationMessages::stockAvailable($this->available, $this->product->product_name);
    }

    /**
     * A short, human sentence for the customer. Never mentions columns, rows,
     * or the database.
     */
    public function customerMessage(): string
    {
        return $this->detail();
    }
}
