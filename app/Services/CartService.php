<?php

namespace App\Services;

use App\Exceptions\StockLimitException;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-based shopping cart for guest customers.
 *
 * PHASE 1: cart lives in the session only.
 * PHASE 2: checkout() persists orders + order_items inside a transaction.
 *
 * Stock discipline: a limit is never applied silently. When a requested
 * quantity cannot be honoured the service refuses and explains, so the
 * controller can tell the customer the real number. Silently trimming the
 * quantity would leave the receipt disagreeing with what they asked for and
 * give them no way to know.
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    /** @return Collection<int, array{product: Product, quantity: int, subtotal: float}> */
    public function items(): Collection
    {
        $cart = Session::get(self::SESSION_KEY, []);

        if (empty($cart)) {
            return collect();
        }

        $products = Product::whereIn('product_id', array_keys($cart))->get()->keyBy('product_id');

        return collect($cart)
            ->filter(fn ($qty, $id) => isset($products[$id]) && $qty > 0)
            ->map(fn ($qty, $id) => [
                'product' => $products[$id],
                'quantity' => (int) $qty,
                'subtotal' => round($products[$id]->price * $qty, 2),
            ])
            ->values();
    }

    /**
     * Lines held in the session whose product no longer exists at all.
     *
     * `items()` drops these so a cart can still render when the catalogue
     * changed underneath it, but dropping one silently would quietly remove a
     * dish the customer believed they had ordered. Callers surface these
     * instead, so the order is never completed without saying so.
     *
     * @return array<int, array{product_id:int, quantity:int}>
     */
    public function missingLines(): array
    {
        $cart = Session::get(self::SESSION_KEY, []);

        if (empty($cart)) {
            return [];
        }

        $found = Product::whereIn('product_id', array_keys($cart))
            ->pluck('product_id')
            ->all();

        return collect($cart)
            ->reject(fn ($qty, $id) => in_array((int) $id, $found, true))
            ->map(fn ($qty, $id) => [
                'product_id' => (int) $id,
                'quantity' => (int) $qty,
            ])
            ->values()
            ->all();
    }

    /**
     * How many of a product may still be added, given what is already held.
     */
    public function remainingFor(Product $product): int
    {
        $held = (int) (Session::get(self::SESSION_KEY, [])[$product->product_id] ?? 0);

        return max(0, $product->quantity - $held);
    }

    /**
     * @throws StockLimitException
     */
    public function add(int $productId, int $quantity = 1): void
    {
        $product = Product::findOrFail($productId);
        $quantity = max(1, $quantity);

        $cart = Session::get(self::SESSION_KEY, []);
        $current = (int) ($cart[$productId] ?? 0);
        $requested = $current + $quantity;

        if ($requested > $product->quantity) {
            throw new StockLimitException($product, $product->quantity, $requested);
        }

        $cart[$productId] = $requested;

        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Set an exact quantity. Removal is a separate, explicit action so a
     * quantity can never quietly become zero.
     *
     * @throws StockLimitException
     */
    public function update(int $productId, int $quantity): void
    {
        $cart = Session::get(self::SESSION_KEY, []);

        if (! isset($cart[$productId])) {
            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            $this->remove($productId);

            return;
        }

        if ($quantity > $product->quantity) {
            throw new StockLimitException($product, $product->quantity, $quantity);
        }

        $cart[$productId] = max(1, $quantity);

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Put a removed line back, for the Undo affordance.
     *
     * @throws StockLimitException
     */
    public function restore(int $productId, int $quantity): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $this->add($productId, max(1, $quantity));
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function total(): float
    {
        return round($this->items()->sum('subtotal'), 2);
    }
}
