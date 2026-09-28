<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderProcessingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Contracts for the validation and failure follow-up.
 *
 * The governing rule across this file: a person is told what happened, what it
 * means, and what to do next - in the words of the brief, not in the words of
 * the database. Nothing technical leaks, nothing valid is thrown away, and
 * nothing is quietly "fixed" behind the customer's back.
 */
class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function matcha(): Product
    {
        // Stocked at 4 in the seeder: the boundary case for every stock rule.
        return Product::where('product_name', 'Matcha Latte')->firstOrFail();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * There is no Order factory, so an order is built the way the application
     * builds one. `Order` has no status constants either; the statuses are the
     * plain strings the rest of the code compares against.
     */
    private function order(string $status = 'Pending'): Order
    {
        return Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
            'status' => $status,
            'total_amount' => 290,
        ]);
    }

    /* ---------------------------------------------------------------
     | Customer checkout: names, quantities, stock
     * ------------------------------------------------------------- */

    public function test_a_missing_name_asks_for_a_name_in_plain_words(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => '   ',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(
            'Please enter your name.',
            session('errors')->first('customer_name')
        );
    }

    public function test_a_name_with_numbers_is_refused_and_the_typing_is_kept_for_correction(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => 'Juan123',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(
            'Name can contain letters, spaces, hyphens and apostrophes only.',
            session('errors')->first('customer_name')
        );

        // The rejected text stays in the box so it can be corrected, rather
        // than being silently discarded and retyped from nothing.
        $this->assertSame('Juan123', session('_old_input')['customer_name'] ?? null);

        // And no order was written.
        $this->assertSame(0, Order::count());
    }

    public function test_an_unusual_but_real_name_is_accepted(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->post(route('cart.checkout'), [
            'customer_name' => "Ana-María O'Neill Jr.",
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.confirmation'));

        $this->assertDatabaseHas('orders', ['customer_name' => "Ana-María O'Neill Jr."]);
    }

    public function test_a_name_is_trimmed_before_it_is_stored(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->post(route('cart.checkout'), [
            'customer_name' => '   Juan Dela Cruz   ',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.confirmation'));

        // Trailing whitespace in a name on a receipt is a small thing that
        // looks careless.
        $this->assertDatabaseHas('orders', ['customer_name' => 'Juan Dela Cruz']);
    }

    public function test_checkout_requires_a_service_type_and_offers_the_choices(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => '',
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(
            'Please select a service type.',
            session('errors')->first('service_type')
        );

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Dine-in')
            ->assertSee('Takeout');
    }

    public function test_checkout_keeps_the_valid_half_of_a_rejected_form(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        // Only the name is wrong. The chosen service must survive the round
        // trip, otherwise the customer re-picks it for no reason.
        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => '',
            'service_type' => 'Takeout',
        ]);

        $this->assertSame('Takeout', session('_old_input')['service_type'] ?? null);
    }

    public function test_a_quantity_below_one_tells_the_customer_the_way_out(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 0,
        ])->assertRedirect(route('cart.index'));

        $message = session('errors')->first('quantity');

        $this->assertSame(
            'Quantity must be at least 1. Use Remove to take an item out of your order.',
            $message
        );
    }

    public function test_a_fractional_quantity_is_refused(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => '1.5',
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(
            'Quantity must be a whole number.',
            session('errors')->first('quantity')
        );
    }

    public function test_stock_is_named_with_the_real_count_and_the_request(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 99,
        ]);

        // The brief's exact sentence, then the specific request that missed it.
        $this->assertSame([
            'Only 4 Matcha Latte items are currently available.',
            'You asked for 99.',
        ], session('errors')->get('quantity'));
    }

    public function test_stock_is_never_silently_reduced_to_what_is_available(): void
    {
        $matcha = $this->matcha();

        $this->from(route('menu'))->post(route('cart.add'), [
            'product_id' => $matcha->product_id,
            'quantity' => 40,
        ]);

        // Nothing was clamped: the cart holds nothing, so the customer must be
        // told rather than quietly handed four.
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotEmpty(session('errors')->get('quantity'));
    }

    public function test_checkout_rechecks_stock_after_the_item_was_already_chosen(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 4]);

        // Someone else takes the last four between choosing and paying.
        $matcha->update(['quantity' => 0]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertStringContainsString(
            'currently available',
            implode(' ', session('errors')->all())
        );
    }

    public function test_checkout_reports_a_dish_that_has_been_deleted_entirely(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        // The row itself is gone, not merely sold out.
        $matcha->delete();

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Dine-in',
        ])->assertRedirect(route('cart.index'));

        // Silently dropping the line would complete an order that is not the
        // one the customer built, so checkout refuses and says why.
        $this->assertDatabaseCount('orders', 0);
        $this->assertStringContainsString(
            'no longer on the menu',
            implode(' ', session('errors')->all())
        );
    }

    public function test_a_vanished_dish_is_visible_in_the_cart_with_a_way_to_drop_it(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);
        $matcha->delete();

        $html = $this->get(route('cart.index'))->getContent();

        $this->assertStringContainsString('Item #'.$matcha->product_id.' is no longer on the menu.', $html);

        // And the form that removes it is still reachable without JavaScript.
        $this->assertStringContainsString('name="product_id" value="'.$matcha->product_id.'"', $html);
    }

    public function test_an_empty_cart_is_told_before_any_order_is_written(): void
    {
        $this->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Dine-in',
        ])->assertSessionHas('error', 'Your order is empty.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_quantity_control_never_offers_more_than_the_server_allows(): void
    {
        // Well stocked on purpose: the control must follow the house per-line
        // limit, not the size of the stock pile.
        $coffee = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();
        $coffee->update(['quantity' => 500]);

        $this->post(route('cart.add'), ['product_id' => $coffee->product_id, 'quantity' => 1]);

        $html = $this->get(route('cart.index'))->getContent();

        $this->assertStringContainsString('max="'.config('kapecha.max_per_line').'"', $html);
        $this->assertStringNotContainsString('max="500"', $html);

        // And the number the control refuses is the number the server refuses.
        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $coffee->product_id,
            'quantity' => 500,
        ])->assertSessionHasErrors('quantity');
    }

    public function test_the_menu_control_never_offers_more_than_the_server_allows(): void
    {
        $coffee = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();
        $coffee->update(['quantity' => 500]);

        $html = $this->get(route('menu'))->getContent();

        $this->assertStringContainsString('max="'.config('kapecha.max_per_line').'"', $html);
        $this->assertStringNotContainsString('max="500"', $html);
    }

    public function test_a_rejected_line_error_is_pointed_at_by_the_input(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);
        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 99,
        ]);

        $html = $this->get(route('cart.index'))->getContent();

        // aria-describedby has to name the element that actually exists, or a
        // screen reader announces the error as silence.
        $this->assertStringContainsString(
            'aria-describedby="quantity-error-'.$matcha->product_id.'"',
            $html
        );
        $this->assertStringNotContainsString(
            'aria-describedby="line-error-',
            $html
        );
    }

    /* ---------------------------------------------------------------
     | Login: no disclosure of which half was wrong
     * ------------------------------------------------------------- */

    public function test_login_says_nothing_about_which_field_was_wrong(): void
    {
        $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'admin',
            'password' => 'not-the-password',
        ]);

        $this->assertSame('Invalid username or password.', session('errors')->first('username'));
    }

    public function test_login_never_echoes_the_password_back(): void
    {
        $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'admin',
            'password' => 'hunter2-secret',
        ]);

        // The username is a legitimate thing to retype; the password is not.
        $this->assertSame('admin', session('_old_input')['username'] ?? null);
        $this->assertArrayNotHasKey('password', session('_old_input') ?? []);
    }

    public function test_login_trims_the_username_before_looking_it_up(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'barista',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.attempt'), [
            'username' => '  barista  ',
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_signed_in_operator_can_leave_and_is_told_so(): void
    {
        $this->actingAs($this->admin())
            ->post(route('logout'))
            ->assertSessionHas('success', 'Logged out successfully.');

        $this->assertGuest();
    }

    /* ---------------------------------------------------------------
     | Products: the same specific treatment
     * ------------------------------------------------------------- */

    public function test_a_blank_product_name_is_refused_with_the_field_named(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'product_name' => '   ',
                'category' => 'Milk Tea',
                'price' => 145,
                'quantity' => 10,
            ])
            ->assertRedirect(route('admin.products.create'));

        $this->assertSame('Enter a product name.', session('errors')->first('product_name'));
        $this->assertDatabaseCount('products', 12);
    }

    public function test_a_price_below_zero_is_refused_rather_than_stored(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'product_name' => 'Pani Pop',
                'category' => 'Pastry',
                'price' => -5,
                'quantity' => 10,
            ])
            ->assertRedirect(route('admin.products.create'));

        $this->assertSame('Enter a price of 0 or more.', session('errors')->first('price'));
        $this->assertDatabaseMissing('products', ['product_name' => 'Pani Pop']);
    }

    public function test_a_duplicate_product_name_is_refused_by_name(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'product_name' => 'Matcha Latte',
                'category' => 'Milk Tea',
                'price' => 150,
                'quantity' => 10,
            ])
            ->assertRedirect(route('admin.products.create'));

        $this->assertSame(
            'A product with this name already exists.',
            session('errors')->first('product_name')
        );
    }

    public function test_editing_a_product_does_not_collide_with_itself(): void
    {
        $admin = $this->admin();
        $matcha = $this->matcha();

        $this->actingAs($admin)->put(route('admin.products.update', $matcha), [
            'product_name' => 'Matcha Latte',
            'category' => 'Milk Tea',
            'price' => 155,
            'quantity' => 4,
        ])->assertRedirect(route('admin.products.index'));

        // Saving unchanged must not be reported as a duplicate of itself.
        $this->assertNull(session('errors'));
        $this->assertDatabaseHas('products', [
            'product_id' => $matcha->product_id,
            'price' => 155,
        ]);
    }

    public function test_a_removed_product_is_announced_in_plain_words(): void
    {
        $matcha = $this->matcha();

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $matcha))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product "Matcha Latte" deleted.');

        $this->assertDatabaseMissing('products', ['product_id' => $matcha->product_id]);
    }

    /* ---------------------------------------------------------------
     | Accounts: passwords are never casually set
     * ------------------------------------------------------------- */

    public function test_a_new_account_needs_a_confirmed_password(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'username' => 'newbarista',
                'role' => User::ROLE_STAFF,
                'password' => 'secret123',
                'password_confirmation' => 'secret124',
            ])
            ->assertRedirect(route('admin.users.create'));

        // The message belongs to the field that has to change.
        $this->assertSame(
            'Passwords do not match.',
            session('errors')->first('password_confirmation')
        );
        $this->assertDatabaseMissing('users', ['username' => 'newbarista']);
    }

    public function test_a_known_username_is_refused_with_a_clear_message(): void
    {
        User::factory()->staff()->create(['username' => 'taken']);

        $this->actingAs($this->admin())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'username' => 'taken',
                'role' => User::ROLE_STAFF,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.users.create'));

        $this->assertSame(
            'That username is already taken.',
            session('errors')->first('username')
        );
    }

    public function test_a_new_account_is_created_with_the_password_hashed(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'username' => 'newbarista',
                'role' => User::ROLE_STAFF,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User "newbarista" created successfully.');

        $user = User::where('username', 'newbarista')->firstOrFail();

        // Stored as a hash, never as the typed text.
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_editing_an_account_without_a_password_keeps_the_old_one(): void
    {
        $staff = User::factory()->staff()->create(['password' => Hash::make('original123')]);

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $staff), [
                'username' => $staff->username,
                'role' => User::ROLE_ADMIN,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('original123', $staff->fresh()->password));
        $this->assertSame(User::ROLE_ADMIN, $staff->fresh()->role);
    }

    public function test_an_invalid_role_is_refused(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($this->admin())
            ->from(route('admin.users.edit', $staff))
            ->put(route('admin.users.update', $staff), [
                'username' => $staff->username,
                'role' => 'Owner',
            ])
            ->assertRedirect(route('admin.users.edit', $staff));

        $this->assertSame('Choose Admin or Staff.', session('errors')->first('role'));
        $this->assertSame(User::ROLE_STAFF, $staff->fresh()->role);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        // The seeder ships an admin of its own, so every other admin has to be
        // demoted before there is exactly one left to try to delete.
        User::where('role', User::ROLE_ADMIN)->update(['role' => User::ROLE_STAFF]);
        $keeper = User::factory()->admin()->create();

        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());

        $this->actingAs($keeper)
            ->delete(route('admin.users.destroy', $keeper))
            ->assertSessionHas('error', 'This is the only Admin account and cannot be deleted.');

        $this->assertDatabaseHas('users', ['id' => $keeper->id]);
    }

    public function test_an_admin_who_has_processed_orders_is_kept_for_the_record(): void
    {
        $keeper = $this->admin();
        $worker = User::factory()->staff()->create();
        $order = $this->order('Completed');
        $order->update(['processed_by' => $worker->id]);

        $this->actingAs($keeper)
            ->delete(route('admin.users.destroy', $worker))
            ->assertSessionHas('error');

        // The refusal explains itself rather than surfacing a foreign key error.
        $this->assertStringContainsString(
            'Change the role instead of deleting it.',
            session('error')
        );
        $this->assertDatabaseHas('users', ['id' => $worker->id]);
    }

    /* ---------------------------------------------------------------
     | Staff: refusals in words, and nothing technical on screen
     * ------------------------------------------------------------- */

    public function test_a_completed_order_cannot_be_started_again(): void
    {
        $order = $this->order('Completed');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('error', 'This order has already been completed.');

        $this->assertSame('Completed', $order->fresh()->status);
    }

    public function test_a_cancelled_order_cannot_be_started(): void
    {
        $order = $this->order('Cancelled');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order))
            ->assertSessionHas('error', 'This order has been cancelled and cannot be processed.');

        $this->assertSame('Cancelled', $order->fresh()->status);
    }

    public function test_a_blocked_order_says_the_transaction_was_never_started(): void
    {
        $order = $this->order('Completed');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order));

        // "Nothing was changed" is the fact the operator actually needs.
        $this->assertStringContainsString('No rows were modified.', implode(' ', session('tx_log')));
    }

    public function test_an_order_cannot_be_cancelled_after_it_is_completed(): void
    {
        $order = $this->order('Completed');

        $this->actingAs(User::factory()->staff()->create())
            ->from(route('staff.orders.show', $order))
            ->post(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertStringContainsString('can no longer be cancelled', session('error'));
        $this->assertSame('Completed', $order->fresh()->status);
    }

    public function test_a_database_failure_during_processing_is_reported_without_the_driver_message(): void
    {
        $order = $this->order('Pending');
        $order->update(['processed_by' => null]);

        // A real QueryException, raised where the controller can actually meet
        // one. The driver's own message is the thing that must not escape.
        $this->processorFailsWithDatabaseError();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('error', 'Order processing failed. No inventory changes were saved.')
            ->assertSessionHas('tx_outcome', 'ROLLBACK');

        $this->assertSame('Pending', $order->fresh()->status);
    }

    public function test_a_database_failure_never_reaches_the_operator_as_an_exception(): void
    {
        $order = $this->order('Pending');
        $order->update(['processed_by' => null]);

        $this->processorFailsWithDatabaseError();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order));

        $html = $this->get(route('staff.orders.show', $order))->getContent();

        $this->assertStringContainsString('No inventory changes were saved.', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $this->assertStringNotContainsString('QueryException', $html);
        $this->assertStringNotContainsString('update `orders`', $html);
        $this->assertStringNotContainsString('Lock wait timeout', $html);
    }

    public function test_an_unexpected_failure_still_gives_the_operator_a_readable_page(): void
    {
        $order = $this->order('Pending');
        $order->update(['processed_by' => null]);

        $this->mock(OrderProcessingService::class, function ($mock): void {
            $mock->shouldReceive('process')->andThrow(new \LogicException('a private internal detail'));
            $mock->shouldReceive('log')->andReturn([]);
        });

        $response = $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.process', $order));

        // The controller only knows how to speak about database trouble, so
        // anything else falls through to the shared server-error page - which
        // is the point: no raw exception reaches the screen either.
        $response->assertStatus(500);

        $html = $response->getContent();

        $this->assertStringContainsString('Something Went Wrong', $html);
        $this->assertStringNotContainsString('a private internal detail', $html);
        $this->assertStringNotContainsString('LogicException', $html);

        // And nothing was written.
        $this->assertSame('Pending', $order->fresh()->status);
    }

    /**
     * A QueryException carrying the sort of driver detail MySQL produces.
     */
    private function driverFailure(): QueryException
    {
        return new QueryException(
            'mysql',
            'update `orders` set `status` = ? where `order_id` = ?',
            ['Completed', 1],
            new \Exception('SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded')
        );
    }

    /**
     * Stand the processor in for one that fails against the database.
     *
     * `log()` is stubbed because the controller reads the trace back while
     * building its response, and an unstubbed call on a mock would replace the
     * failure under test with a failure of the test itself.
     */
    private function processorFailsWithDatabaseError(): void
    {
        $this->mock(OrderProcessingService::class, function ($mock): void {
            $mock->shouldReceive('process')->andThrow($this->driverFailure());
            $mock->shouldReceive('log')->andReturn([]);
        });
    }

    /* ---------------------------------------------------------------
     | System: the error pages, and no internals on any of them
     * ------------------------------------------------------------- */

    /**
     * @dataProvider errorPages
     */
    public function test_each_error_page_prints_its_own_copy(string $view, string $heading): void
    {
        $view = $this->view($view, ['code' => '000']);

        $view->assertSee($heading);

        // A server problem must never be dressed up as a page a person can
        // act on, so none of these may leak internals.
        $view->assertDontSee('SQLSTATE');
        $view->assertDontSee('PDOException');
        $view->assertDontSee('QueryException');
        $view->assertDontSee('vendor\\laravel');
        $view->assertDontSee('stack trace');
    }

    public static function errorPages(): array
    {
        return [
            'gone' => ['errors.403', 'Access Denied'],
            'lost' => ['errors.404', 'Page Not Found'],
            'stale form' => ['errors.419', 'Session Expired'],
            'unprocessable' => ['errors.422', 'Check Your Details'],
            'too fast' => ['errors.429', 'Too Many Requests'],
            'broken' => ['errors.500', 'Something Went Wrong'],
        ];
    }

    public function test_a_missing_page_is_a_plain_404_not_a_stack_trace(): void
    {
        $this->get('/no-such-counter-anywhere')
            ->assertNotFound()
            ->assertSee('Page Not Found');
    }

    public function test_a_guest_is_sent_to_login_rather_than_shown_a_blank_error(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }
}
