<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contracts for the usability pass: quiet design, loud actions.
 *
 * These assert the *behaviour* the brief asked for - a stock limit that is
 * explained rather than silently applied, an undo that actually undoes, a
 * destructive action that cannot be reached in one careless tap, a status a
 * customer can read without decoding a colour.
 */
class InteractionClarityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function matcha(): Product
    {
        // Stocked at 4 in the seeder, which makes it the useful boundary case.
        return Product::where('product_name', 'Matcha Latte')->firstOrFail();
    }

    /* ---------------------------------------------------------------
     | Stock limits are explained, never silently applied
     * ------------------------------------------------------------- */

    public function test_adding_beyond_stock_is_refused_with_a_plain_explanation(): void
    {
        $matcha = $this->matcha();

        $response = $this->from(route('menu'))->post(route('cart.add'), [
            'product_id' => $matcha->product_id,
            'quantity' => 9,
        ]);

        $response->assertRedirect(route('menu'));
        $response->assertSessionHasErrors('quantity');

        // The message names the real number and speaks like a person.
        $this->assertStringContainsString('Only 4 Matcha Latte', session('errors')->first('quantity'));

        // Nothing was quietly trimmed into the cart.
        $this->assertEmpty(session('cart', []));
    }

    public function test_the_menu_explains_a_refused_addition_where_it_happened(): void
    {
        $matcha = $this->matcha();

        $this->from(route('menu'))->post(route('cart.add'), [
            'product_id' => $matcha->product_id,
            'quantity' => 9,
        ]);

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Only 4 Matcha Latte', false);
    }

    public function test_raising_a_cart_line_above_stock_is_refused_and_explained(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 2]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 40,
        ])->assertSessionHasErrors('quantity');

        $this->assertStringContainsString('Only 4 Matcha Latte', session('errors')->first('quantity'));

        // The existing quantity is untouched.
        $this->assertSame(2, session('cart')[$matcha->product_id]);
    }

    public function test_a_quantity_below_one_is_rejected_with_guidance(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 0,
        ])->assertSessionHasErrors('quantity');

        $this->assertStringContainsString('at least 1', session('errors')->first('quantity'));
        $this->assertStringContainsString('Remove', session('errors')->first('quantity'));
    }

    public function test_checkout_rechecks_stock_before_writing_an_order(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 4]);

        // Another till takes the case while this order waits in the tray.
        $matcha->update(['quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(0, Order::count(), 'No order may be written when stock has gone.');
    }

    /* ---------------------------------------------------------------
     | A limit is stated once, on the thing it is about
     * ------------------------------------------------------------- */

    public function test_the_limit_names_the_dish_and_the_real_count(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);
        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 99,
        ]);

        // The specific number, plus what was actually asked for.
        $this->assertSame(
            ['Only 4 Matcha Latte items are currently available.', 'You asked for 99.'],
            session('errors')->get('quantity')
        );

        // The headline says a limit was hit at all.
        $this->assertSame('Not enough stock available.', session('warning'));
    }

    public function test_a_refused_add_is_reported_only_on_the_dish_that_failed(): void
    {
        $matcha = $this->matcha();
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();

        $this->from(route('menu'))->post(route('cart.add'), [
            'product_id' => $matcha->product_id,
            'quantity' => 9,
        ]);

        $html = $this->get(route('menu'))->getContent();

        // The message appears twice by design: once in the summary at the top
        // so every problem is visible at a glance, once beside the field that
        // has to change. What must not happen is an error element appearing
        // on the eleven other dishes.
        $this->assertSame(1, substr_count($html, 'id="qty-error-'.$matcha->product_id.'"'));
        $this->assertSame(0, substr_count($html, 'id="qty-error-'.$latte->product_id.'"'));
        $this->assertSame(2, substr_count($html, 'Only 4 Matcha Latte items are currently available.'));
    }

    public function test_a_refused_line_is_flagged_on_its_own_line_only(): void
    {
        $matcha = $this->matcha();
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.update'), [
            'product_id' => $matcha->product_id,
            'quantity' => 99,
        ]);

        $html = $this->get(route('cart.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'id="quantity-error-'.$matcha->product_id.'"'));
        $this->assertSame(0, substr_count($html, 'id="quantity-error-'.$latte->product_id.'"'));
    }

    public function test_the_upper_bound_counts_what_is_left_not_what_is_on_the_shelf(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        // The first load confirms the addition; the next is the menu as the
        // customer sees it when they come back to it.
        $this->get(route('menu'));
        $html = $this->get(route('menu'))->getContent();

        // One of the four is already in the order, so three may be added.
        $this->assertStringContainsString('Up to 3 more can be added.', $html);
        $this->assertStringNotContainsString('Up to 4 more can be added.', $html);
    }

    public function test_the_last_one_available_is_described_in_words(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 3]);

        $this->get(route('menu'));
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('One more can be added.');
    }

    public function test_a_dish_fully_in_the_order_says_so_and_offers_the_change(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 4]);

        $this->get(route('menu'));
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('All 4 in your order.')
            ->assertSee('Change the amount in your order');
    }

    /* ---------------------------------------------------------------
     | Editing an order
     * ------------------------------------------------------------- */

    public function test_a_removed_line_can_be_put_back(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 3]);

        $this->post(route('cart.remove'), ['product_id' => $matcha->product_id])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertArrayNotHasKey($matcha->product_id, session('cart'));

        // The confirmation offers the way back.
        $this->get(route('cart.index'))->assertSee('Undo');

        $this->post(route('cart.restore'))->assertRedirect(route('cart.index'));

        $this->assertSame(3, session('cart')[$matcha->product_id]);
    }

    public function test_undo_reports_honestly_when_the_stock_is_gone(): void
    {
        $matcha = $this->matcha();

        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 4]);
        $this->post(route('cart.remove'), ['product_id' => $matcha->product_id]);

        $matcha->update(['quantity' => 0]);

        $this->post(route('cart.restore'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('warning');

        $this->assertArrayNotHasKey($matcha->product_id, session('cart', []));
    }

    public function test_the_cart_offers_a_stepper_and_a_dominant_checkout(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $this->get(route('cart.index'))
            ->assertOk()
            // A real stepper, not a bare number box.
            ->assertSee('data-stepper', false)
            ->assertSee('kc-btn--primary', false)
            ->assertSee('Confirm order')
            ->assertSee('Edit order')
            // The phone-only bar carries the same primary action.
            ->assertSee('kc-sticky-cta', false)
            ->assertSee('Checkout');
    }

    public function test_an_empty_cart_says_so_and_offers_a_way_forward(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Your Order Is Empty')
            // Raw, because the typographic apostrophe is an HTML entity.
            ->assertSee('You haven&rsquo;t added anything to your order yet.', false)
            ->assertSee('Browse Menu');
    }

    public function test_editing_the_cart_never_creates_a_second_order(): void
    {
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();

        $this->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 2]);

        // Change the order as much as the customer likes...
        $this->post(route('cart.update'), ['product_id' => $latte->product_id, 'quantity' => 3]);
        $this->post(route('cart.remove'), ['product_id' => $latte->product_id]);
        $this->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 1]);

        $this->assertSame(0, Order::count(), 'Editing the tray must not write an order.');

        // ...and only the final confirm creates exactly one.
        $this->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
        ])->assertRedirect(route('cart.confirmation'));

        $this->assertSame(1, Order::count());
    }

    /* ---------------------------------------------------------------
     | System messages
     * ------------------------------------------------------------- */

    public function test_the_empty_state_never_shows_a_technical_error(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('SQLSTATE', false)
            ->assertDontSee('Undefined variable', false)
            ->assertDontSee('500 Server Error', false);
    }

    public function test_validation_errors_are_announced_and_counted(): void
    {
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 1]);

        $this->from(route('cart.index'))->post(route('cart.checkout'), [
            'customer_name' => '',
            'service_type' => '',
        ])->assertSessionHasErrors(['customer_name', 'service_type']);

        $this->get(route('cart.index'))
            ->assertOk()
            // An error is a live region, so a screen reader announces it.
            ->assertSee('role="alert"', false)
            ->assertSee('Check the form')
            // The customer is asked for a name in plain words.
            ->assertSee('Please enter your name.')
            ->assertSee('Please select a service type.');
    }

    public function test_an_empty_cart_is_told_to_go_and_choose_something(): void
    {
        $this->post(route('cart.checkout'), [
            'customer_name' => '',
            'service_type' => '',
        ])->assertSessionHas('error', 'Your order is empty.');

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Your Order Is Empty');
    }

    /* ---------------------------------------------------------------
     | Staff: processing and cancellation
     * ------------------------------------------------------------- */

    private function pendingOrder(): Order
    {
        $product = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
            'status' => 'Pending',
            'total_amount' => $product->price * 2,
        ]);

        OrderItem::create([
            'order_id' => $order->order_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2,
        ]);

        return $order;
    }

    public function test_staff_can_cancel_a_pending_order(): void
    {
        $order = $this->pendingOrder();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('Cancelled', $order->fresh()->status);
    }

    public function test_a_completed_order_can_no_longer_be_cancelled(): void
    {
        $order = $this->pendingOrder();
        $order->update(['status' => 'Completed']);

        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('staff.orders.cancel', $order))
            ->assertSessionHas('error');

        $this->assertSame('Completed', $order->fresh()->status);
    }

    public function test_cancelling_an_order_never_touches_stock(): void
    {
        $order = $this->pendingOrder();
        $before = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail()->quantity;

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('staff.orders.cancel', $order));

        $this->assertSame($before, Product::where('product_name', 'Iced Spanish Latte')->firstOrFail()->quantity);
    }

    public function test_a_guest_cannot_cancel_an_order(): void
    {
        $order = $this->pendingOrder();

        $this->post(route('staff.orders.cancel', $order))->assertRedirect(route('login'));

        $this->assertSame('Pending', $order->fresh()->status);
    }

    public function test_the_ticket_states_its_status_in_words(): void
    {
        $order = $this->pendingOrder();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('staff.orders.show', $order))
            ->assertOk()
            ->assertSee('Order Status')
            ->assertSee('Pending')
            ->assertSee('Your order is waiting to be processed.')
            // The primary action and the destructive one are distinguishable.
            ->assertSee('Process order')
            ->assertSee('Cancel order')
            ->assertSee('kc-btn--danger', false);
    }

    /* ---------------------------------------------------------------
     | The transaction outcome is actually shown
     * ------------------------------------------------------------- */

    public function test_a_commit_is_reported_to_the_operator(): void
    {
        $order = $this->pendingOrder();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('staff.orders.process', $order));

        $this->followingRedirects()
            ->post(route('staff.orders.process', $this->pendingOrder()))
            ->assertSee('Processed');
    }

    public function test_a_rollback_is_reported_to_the_operator(): void
    {
        $order = $this->pendingOrder();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->followingRedirects()
            ->post(route('staff.orders.process', $order), ['simulate_failure' => 1])
            ->assertSee('Rolled back');
    }

    /* ---------------------------------------------------------------
     | Catalogue affordances
     * ------------------------------------------------------------- */

    public function test_add_to_order_is_a_button_not_a_word(): void
    {
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Add to order')
            ->assertSee('kc-btn--primary', false);
    }

    public function test_a_sold_out_dish_says_why_it_cannot_be_added(): void
    {
        $matcha = $this->matcha();
        $matcha->update(['quantity' => 0]);

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Sold out today.');
    }

    public function test_a_just_added_dish_is_confirmed_in_place(): void
    {
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();

        $this->post(route('cart.add'), [
            'product_id' => $latte->product_id,
            'quantity' => 2,
        ])->assertSessionHas('added_product_id', $latte->product_id);

        $this->followingRedirects()
            ->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 2])
            ->assertSee('Iced Spanish Latte is in your order.')
            ->assertSee('View order');
    }

    /* ---------------------------------------------------------------
     | Destructive actions ask first
     * ------------------------------------------------------------- */

    public function test_deleting_a_product_requires_a_second_press_in_a_dialog(): void
    {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)->get(route('admin.products.index'))->getContent();

        $this->assertStringContainsString('data-confirm-open', $html);
        $this->assertStringContainsString('<dialog', $html);
        $this->assertStringContainsString('kc-btn--danger', $html);

        // The one-tap path must be gone: no bare DELETE form on the page.
        $this->assertStringNotContainsString('data-confirm="', $html);
    }

    public function test_the_cart_remove_control_asks_before_removing(): void
    {
        $latte = Product::where('product_name', 'Iced Spanish Latte')->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $latte->product_id, 'quantity' => 1]);

        $html = $this->get(route('cart.index'))->getContent();

        $this->assertStringContainsString('data-confirm-open', $html);
        $this->assertStringContainsString('<dialog', $html);
    }

    /* ---------------------------------------------------------------
     | The enhancements are actually shipped
     * ------------------------------------------------------------- */

    public function test_the_built_bundle_carries_the_progressive_enhancements(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        $this->assertArrayHasKey('resources/js/app.js', $manifest, 'The JS entry is missing from the manifest.');
        $this->assertArrayHasKey('resources/css/app.css', $manifest);

        $js = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        // Stepper, auto-save, busy state, dialog. If the build ever drops the
        // script these go quiet and the checks below are the only warning.
        foreach (['stepper', 'autosubmit', 'loadingLabel', 'aria-busy', 'showModal'] as $hook) {
            $this->assertStringContainsString($hook, $js, "The built script lost the {$hook} behaviour.");
        }

        // The state styling the hooks rely on.
        foreach (['.kc-btn--primary', '.kc-btn--danger', '.kc-btn__spinner', '.kc-stepper',
            '.kc-msg--error', '.kc-status-block', '.kc-sticky-cta', '.kc-dialog'] as $selector) {
            $this->assertStringContainsString($selector, $css, "The built stylesheet lost {$selector}.");
        }
    }

    /* ---------------------------------------------------------------
     | Permission
     * ------------------------------------------------------------- */

    public function test_the_permission_page_says_so_plainly(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden()
            ->assertSee('Access Denied')
            // Rendered through the shared error layout, so the apostrophe is
            // HTML-escaped in the source exactly as a browser would receive it.
            ->assertSee("You don't have permission to access this page.");
    }

    public function test_login_does_not_reveal_which_field_was_wrong(): void
    {
        $this->from(route('login'))->post(route('login.attempt'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $message = session('errors')->first('username');

        // One sentence covering both fields: naming the failing one would
        // confirm which usernames exist to anyone who tried one.
        $this->assertSame('Invalid username or password.', $message);
        $this->assertStringNotContainsString('password is', strtolower($message));
        $this->assertStringNotContainsString('username is', strtolower($message));
        $this->assertStringNotContainsString('does not exist', strtolower($message));
    }
}
