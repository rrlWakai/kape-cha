<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /* TEST 1: Login page appears */
    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Secure Login');
    }

    /* TEST 2: Invalid credentials rejected */
    public function test_invalid_login_rejected(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /* TEST 3: Admin login → Admin dashboard */
    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Executive Management');
    }

    /* TEST 4: Staff login → Staff dashboard */
    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $staff = User::where('username', 'staff')->first();

        $response = $this->post('/login', [
            'username' => 'staff',
            'password' => 'staff123',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticatedAs($staff);

        $this->actingAs($staff)
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('POS Dispatch');
    }

    /* TEST 5: Staff cannot access /admin/products (server-side 403) */
    public function test_staff_cannot_access_admin_products(): void
    {
        $staff = User::where('username', 'staff')->first();

        $this->actingAs($staff)
            ->get('/admin/products')
            ->assertForbidden()
            ->assertSee('Access Denied');
    }

    /* TEST 5b: Staff cannot even be granted Admin by URL manipulation */
    public function test_staff_cannot_access_admin_dashboard(): void
    {
        $staff = User::where('username', 'staff')->first();

        $this->actingAs($staff)->get('/admin/dashboard')->assertForbidden();
    }

    /* TEST 6: Guest visiting /admin/dashboard is redirected to login */
    public function test_guest_redirected_from_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    /* TEST 7: Logout terminates session */
    public function test_logout_invalidates_session(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
    }

    /* TEST 8: Public menu accessible without login */
    public function test_public_menu_accessible_without_login(): void
    {
        $this->get('/menu')
            ->assertOk()
            ->assertSee('Iced Spanish Latte')
            ->assertSee('Brown Sugar Milk Tea');
    }

    /* TEST 9: Customer adds product to cart (session) */
    public function test_customer_can_add_product_to_cart(): void
    {
        $product = Product::first();

        $this->post(route('cart.add'), [
            'product_id' => $product->product_id,
            'quantity' => 2,
        ])->assertRedirect(route('menu'));

        $cart = session('cart');
        $this->assertArrayHasKey($product->product_id, $cart);
        $this->assertEquals(2, $cart[$product->product_id]);

        $this->get('/cart')->assertOk()->assertSee($product->product_name);
    }

    /* TEST 9b: Checkout produces confirmation and clears the cart */
    public function test_checkout_shows_confirmation(): void
    {
        $product = Product::first();

        $this->post(route('cart.add'), ['product_id' => $product->product_id, 'quantity' => 1]);
        $this->post(route('cart.checkout'), [
            'customer_name' => 'Juan',
            'service_type' => 'Takeout',
        ])->assertRedirect(route('cart.confirmation'));

        $this->get(route('cart.confirmation'))
            ->assertOk()
            ->assertSee('Order Confirmed')
            ->assertSee('Juan');

        $this->assertEmpty(session('cart'));
    }

    /* TEST 10: Admin can create a product via validated CRUD */
    public function test_admin_can_create_product(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)->post('/admin/products', [
            'product_name' => 'Salted Caramel Cold Brew',
            'category' => 'Coffee',
            'price' => 140.00,
            'quantity' => 20,
            'description' => 'Test product',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['product_name' => 'Salted Caramel Cold Brew']);
    }

    /* TEST 11: Product validation rejects bad data */
    public function test_product_validation_rejects_invalid_data(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)->post('/admin/products', [
            'product_name' => 'Iced Spanish Latte', // duplicate
            'category' => 'Coffee',
            'price' => -5,
            'quantity' => 'abc',
        ])->assertSessionHasErrors(['product_name', 'price', 'quantity']);
    }

    /* TEST 12: Passwords are hashed, never plain */
    public function test_passwords_are_hashed_in_database(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->assertStringStartsWith('$2y$', $admin->password);
        $this->assertStringNotContainsString('admin123', $admin->password);
    }
}
