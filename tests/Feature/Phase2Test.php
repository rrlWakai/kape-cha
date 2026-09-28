<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 2 — Database transaction & atomicity test suite.
 *
 * Covers the ITST 302 transaction spec end to end:
 *   BEGIN → lock order + products → verify Pending → verify stock
 *   → deduct inventory → status Completed + processed_by → COMMIT
 * plus every ROLLBACK path and the customer-side order persistence.
 */
class Phase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /**
     * Build a Pending order with a single line item.
     */
    private function makeOrder(Product $product, int $qty, string $status = 'Pending'): Order
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Test Customer',
            'service_type' => 'Takeout',
            'status' => $status,
            'total_amount' => $product->price * $qty,
        ]);

        OrderItem::create([
            'order_id' => $order->order_id,
            'product_id' => $product->product_id,
            'quantity' => $qty,
            'unit_price' => $product->price,
            'subtotal' => $product->price * $qty,
        ]);

        return $order->fresh();
    }

    /* TEST 1: Checkout persists the order header + line items inside a transaction */
    public function test_checkout_persists_order_and_items(): void
    {
        $product = Product::where('product_name', 'Iced Spanish Latte')->first();

        $this->post(route('cart.add'), [
            'product_id' => $product->product_id,
            'quantity' => 2,
        ]);

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Juan Dela Cruz',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.confirmation'));

        $order = Order::with('items')->latest('order_id')->first();

        $this->assertNotNull($order, 'Checkout must persist an order row.');
        $this->assertSame('Pending', $order->status, 'New orders must start as Pending.');
        $this->assertSame('Juan Dela Cruz', $order->customer_name);
        $this->assertSame('Dine-in', $order->service_type);
        $this->assertNull($order->processed_by, 'processed_by stays null until Staff processes it.');
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertEquals($product->price * 2, $order->total_amount);

        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->order_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
        ]);
    }

    /* TEST 2: Checkout stores the order number for the confirmation receipt */
    public function test_confirmation_receipt_matches_stored_order(): void
    {
        $product = Product::where('product_name', 'Caramel Macchiato')->first();
        $this->post(route('cart.add'), ['product_id' => $product->product_id, 'quantity' => 1]);

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
        ]);

        $order = Order::latest('order_id')->first();

        $this->get(route('cart.confirmation'))
            ->assertOk()
            ->assertSee('Order Confirmed')
            ->assertSee($order->order_number)
            ->assertSee('Juan');
    }

    /* TEST 3: HAPPY PATH — Staff commits: stock deducted, status Completed, processed_by set */
    public function test_staff_processing_commits_and_deducts_inventory(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Cafe Mocha')->first();
        $stockBefore = $product->quantity;

        $order = $this->makeOrder($product, 3);

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('commit');

        $order->refresh();

        $this->assertSame('Completed', $order->status, 'Order must be Completed after COMMIT.');
        $this->assertSame($staff->id, $order->processed_by, 'processed_by must record the staff user.');
        $this->assertSame($stockBefore - 3, $product->fresh()->quantity, 'Inventory must be deducted.');
    }

    /* TEST 4: ROLLBACK — insufficient stock leaves order Pending and inventory untouched */
    public function test_processing_rolls_back_when_stock_is_insufficient(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Okinawa Milk Tea')->first();
        $stockBefore = $product->quantity;

        // Order more than the shelf holds.
        $order = $this->makeOrder($product, $stockBefore + 10);

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('rollback');

        $order->refresh();

        $this->assertSame('Pending', $order->status, 'Failed transaction must not change status.');
        $this->assertNull($order->processed_by);
        $this->assertSame($stockBefore, $product->fresh()->quantity, 'Inventory must be rolled back to its original value.');
    }

    /* TEST 5: ATOMICITY — one bad line item rolls back the whole multi-item transaction */
    public function test_rollback_leaves_every_product_untouched_in_multi_item_order(): void
    {
        $staff = User::where('username', 'staff')->first();

        $good = Product::where('product_name', 'Iced Americano')->first();
        $bad = Product::where('product_name', 'Matcha Latte')->first(); // seeded low-stock item

        $goodStock = $good->quantity;
        $badStock = $bad->quantity;

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Multi Item',
            'service_type' => 'Dine-in',
            'status' => 'Pending',
            'total_amount' => ($good->price * 2) + ($bad->price * ($badStock + 5)),
        ]);

        OrderItem::create([
            'order_id' => $order->order_id,
            'product_id' => $good->product_id,
            'quantity' => 2,
            'unit_price' => $good->price,
            'subtotal' => $good->price * 2,
        ]);

        OrderItem::create([
            'order_id' => $order->order_id,
            'product_id' => $bad->product_id,
            'quantity' => $badStock + 5,
            'unit_price' => $bad->price,
            'subtotal' => $bad->price * ($badStock + 5),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order->fresh()))
            ->assertSessionHas('rollback');

        $this->assertSame($goodStock, $good->fresh()->quantity, 'First product must not be deducted when a later item fails.');
        $this->assertSame($badStock, $bad->fresh()->quantity);
        $this->assertSame('Pending', $order->fresh()->status);
    }

    /* TEST 6: ROLLBACK demo hook — simulated failure reverts everything */
    public function test_simulated_failure_triggers_rollback(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Wintermelon Milk Tea')->first();
        $stockBefore = $product->quantity;

        $order = $this->makeOrder($product, 2);

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order), ['simulate_failure' => 1])
            ->assertSessionHas('rollback');

        $this->assertSame('Pending', $order->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->quantity);
    }

    /* TEST 7: Durability guard — a Completed order cannot be processed twice */
    public function test_completed_order_cannot_be_processed_twice(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Brown Sugar Milk Tea')->first();
        $stockBefore = $product->quantity;

        $order = $this->makeOrder($product, 4);

        $this->actingAs($staff)->post(route('staff.orders.process', $order));

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order->fresh()))
            ->assertSessionHas('error');

        $this->assertSame($stockBefore - 4, $product->fresh()->quantity, 'Inventory must only be deducted once.');
        $this->assertSame('Completed', $order->fresh()->status);
    }

    /* TEST 8: Security — guests cannot execute the transaction, Admin may */
    public function test_only_privileged_users_can_process_orders(): void
    {
        $product = Product::where('product_name', 'Iced Spanish Latte')->first();
        $order = $this->makeOrder($product, 1);

        $this->post(route('staff.orders.process', $order))
            ->assertRedirect(route('login'));

        $this->assertSame('Pending', $order->fresh()->status);

        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post(route('staff.orders.process', $order))
            ->assertSessionHas('commit');

        $this->assertSame('Completed', $order->fresh()->status);
    }

    /* TEST 9: Order numbers are generated uniquely and sequentially */
    public function test_order_numbers_are_unique_and_sequential(): void
    {
        $this->assertSame('KC-0001', Order::generateOrderNumber());

        $product = Product::where('product_name', 'Iced Americano')->first();
        $this->makeOrder($product, 1);
        $this->makeOrder($product, 1);

        $numbers = Order::pluck('order_number')->all();

        $this->assertSame(count($numbers), count(array_unique($numbers)), 'Order numbers must be unique.');
        $this->assertSame('KC-0003', Order::generateOrderNumber());
    }

    /* TEST 10: The transaction console renders with the live SQL trace */
    public function test_transaction_console_renders_and_flashes_sql_trace(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Cafe Mocha')->first();
        $order = $this->makeOrder($product, 1);

        $this->actingAs($staff)
            ->get(route('staff.orders.show', $order))
            ->assertOk()
            ->assertSee('ACID Transaction Demonstrator')
            ->assertSee('Live SQL Trace Log')
            ->assertSee('Row-Level Mutex')
            ->assertSee('Prepared statement: ORDER_PROCESSING_TRANSACTION');

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order))
            ->assertSessionHas('tx_outcome', 'COMMIT');

        $trace = session('tx_log');

        $this->assertIsArray($trace);
        $this->assertStringContainsString('BEGIN TRANSACTION;', implode("\n", $trace));
        $this->assertStringContainsString('FOR UPDATE;', implode("\n", $trace));
        $this->assertStringContainsString('UPDATE products SET quantity', implode("\n", $trace));
        $this->assertStringContainsString('COMMIT;', implode("\n", $trace));

        $this->actingAs($staff)
            ->get(route('staff.orders.show', $order))
            ->assertOk()
            ->assertSee('COMMIT;', false);
    }

    /* TEST 11: A rolled-back attempt flashes a ROLLBACK trace instead */
    public function test_rollback_flashes_rollback_trace(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Matcha Latte')->first();
        $order = $this->makeOrder($product, $product->quantity + 1);

        $this->actingAs($staff)
            ->post(route('staff.orders.process', $order))
            ->assertSessionHas('tx_outcome', 'ROLLBACK');

        $trace = implode("\n", session('tx_log'));

        $this->assertStringContainsString('FOR UPDATE;', $trace);
        $this->assertStringContainsString('ROLLBACK;', $trace);
        $this->assertStringNotContainsString('COMMIT;', $trace);
    }

    /* TEST 12: Admin reuses the same screens without leaving the /admin prefix */
    public function test_admin_views_stay_inside_admin_prefix(): void
    {
        $admin = User::where('username', 'admin')->first();
        $product = Product::where('product_name', 'Caramel Macchiato')->first();
        $order = $this->makeOrder($product, 1);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Live Order Queue');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(route('admin.orders.process', $order), false)
            ->assertDontSee(route('staff.orders.process', $order), false);

        $this->actingAs($admin)
            ->post(route('admin.orders.process', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('commit');

        $this->assertSame('Completed', $order->fresh()->status);
    }

    /* TEST 13: Queue tabs filter by status */
    public function test_order_queue_filters_by_status(): void
    {
        $staff = User::where('username', 'staff')->first();
        $product = Product::where('product_name', 'Iced Spanish Latte')->first();

        $pending = $this->makeOrder($product, 1);
        $completed = $this->makeOrder($product, 1, 'Completed');

        $this->actingAs($staff)
            ->get(route('staff.orders.index', ['status' => 'Pending']))
            ->assertOk()
            ->assertSee($pending->order_number)
            ->assertDontSee($completed->order_number);

        $this->actingAs($staff)
            ->get(route('staff.orders.index', ['status' => 'Completed']))
            ->assertOk()
            ->assertSee($completed->order_number)
            ->assertDontSee($pending->order_number);

        $this->actingAs($staff)
            ->get(route('staff.orders.index'))
            ->assertOk()
            ->assertSee($pending->order_number)
            ->assertSee($completed->order_number);
    }
}
