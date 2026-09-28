<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Support\ValidationMessages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PHASE 2 — ACID order processing engine (ITST 302 demonstration).
 *
 * Transaction flow:
 *   BEGIN TRANSACTION
 *     1. Lock order row            (SELECT ... FOR UPDATE)
 *     2. Lock relevant product rows (SELECT ... FOR UPDATE, consistent ID order)
 *     3. Verify order is still Pending
 *     4. Verify every product has enough stock
 *     5. Deduct inventory
 *     6. Order status → Completed, set processed_by
 *   COMMIT
 *
 * Any verification failure throws — Laravel automatically issues ROLLBACK,
 * leaving inventory and order status completely untouched.
 */
class OrderProcessingService
{
    /** @var array<int, string> SQL trace for the ITST 302 live transaction console. */
    private array $log = [];

    /**
     * The trace of the most recent process() call — every statement that ran
     * inside the transaction, including the final COMMIT or ROLLBACK.
     *
     * @return array<int, string>
     */
    public function log(): array
    {
        return $this->log;
    }

    private function trace(string $sql): void
    {
        $this->log[] = '['.now()->format('H:i:s').'] '.$sql;
    }

    /**
     * @throws RuntimeException when the order is not Pending or stock is insufficient
     */
    public function process(Order $order, bool $simulateFailure = false): Order
    {
        $this->log = [];

        try {
            return DB::transaction(function () use ($order, $simulateFailure) {
                $orderId = $order->order_id;
                $orderNumber = $order->order_number;

                // 1. BEGIN
                $this->trace("BEGIN TRANSACTION;  -- ticket #{$orderNumber}");

                // 2. Lock the order row (prevents double-processing / race conditions).
                $order = Order::where('order_id', $orderId)->lockForUpdate()->first();
                $this->trace("SELECT * FROM orders WHERE order_id = {$orderId} FOR UPDATE;  -- row lock acquired");

                if (! $order) {
                    throw new RuntimeException(ValidationMessages::orderProcessing()['order.not_pending']);
                }

                // 3. Verify the order is still Pending.
                if ($order->status !== 'Pending') {
                    $this->trace("ROLLBACK;  -- orders.status = '{$order->status}'");
                    throw new RuntimeException($this->blockedReason($order->status));
                }
                $this->trace("-- CHECK: orders.status = 'Pending'  OK");

                // 4. Lock all relevant product rows in a consistent (ID-sorted) order
                //    so concurrent tickets always acquire locks in the same sequence.
                $items = $order->items()->orderBy('product_id')->get();
                $lockedProducts = [];

                foreach ($items as $item) {
                    $product = Product::where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        throw new RuntimeException(
                            ValidationMessages::orderProcessing()['order.missing_product']
                        );
                    }

                    $this->trace("SELECT * FROM products WHERE product_id = {$item->product_id} FOR UPDATE;  -- \"{$product->product_name}\"");

                    // 5. Verify stock availability. The operator is told which
                    // dish is short, but the headline states the general cause
                    // rather than leaking a constraint name.
                    if ($product->quantity < $item->quantity) {
                        throw new RuntimeException(sprintf(
                            '%s %s: need %d, %d available.',
                            ValidationMessages::orderProcessing()['order.stock'],
                            $product->product_name,
                            $item->quantity,
                            $product->quantity,
                        ));
                    }

                    $this->trace("-- CHECK: products.quantity ({$product->quantity}) >= order qty ({$item->quantity})  OK");

                    $lockedProducts[$item->product_id] = $product;
                }

                // Optional demo hook (assessment video): force a failure after locks
                // are acquired so the ROLLBACK path can be demonstrated live.
                if ($simulateFailure) {
                    throw new RuntimeException(ValidationMessages::transactionFailed());
                }

                // 6. Deduct inventory.
                foreach ($items as $item) {
                    $product = $lockedProducts[$item->product_id];
                    $product->quantity -= $item->quantity;
                    $product->save();

                    $this->trace("UPDATE products SET quantity = {$product->quantity} WHERE product_id = {$product->product_id};");
                }

                // 7 & 8. Mark the order Completed and record the processing staff member.
                $order->status = 'Completed';
                $order->processed_by = Auth::id();
                $order->user_id = Auth::id();
                $order->save();

                $this->trace("UPDATE orders SET status = 'Completed', processed_by = ".Auth::id().", user_id = ".Auth::id()." WHERE order_id = {$order->order_id};");

                // 9. COMMIT
                $this->trace('COMMIT;  -- order + inventory persisted atomically');

                return $order;
            });
        } catch (RuntimeException $e) {
            // QueryException also extends RuntimeException, so DB-level errors are traced too.
            $this->trace('ROLLBACK;  -- '.$e->getMessage());

            throw $e;
        } catch (\Throwable $e) {
            // Anything that is not a RuntimeException — a PDO failure, say —
            // still rolls back, but its message is never handed upward: the
            // controller would otherwise flash a SQLSTATE at the operator.
            $this->trace('ROLLBACK;  -- unexpected failure inside the transaction');

            throw new RuntimeException(ValidationMessages::transactionFailed(), 0, $e);
        }
    }

    /**
     * Why a ticket cannot be processed, in one sentence per status.
     *
     * "Order #12 is 'Completed' — only Pending orders can be processed." tells
     * an operator nothing they did not already see on the badge. Naming the
     * actual reason is the difference between a message and an obstacle.
     */
    private function blockedReason(string $status): string
    {
        $messages = ValidationMessages::orderProcessing();

        return match ($status) {
            'Completed' => $messages['order.completed'],
            'Cancelled' => $messages['order.cancelled'],
            default => $messages['order.not_pending'],
        };
    }
}
