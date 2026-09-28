<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class CheckoutRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function matcha(): Product
    {
        return Product::where('product_name', 'Matcha Latte')->firstOrFail();
    }

    public function test_checkout_rollback_leaves_no_order_row(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $ordersBefore = Order::count();

        OrderItem::creating(function ($item) {
            throw new Exception("Simulated checkout failure");
        });

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Test User',
            'service_type'  => 'Takeout',
        ]);

        $this->assertSame($ordersBefore, Order::count(), 'No order should have been created.');
    }

    public function test_checkout_rollback_leaves_no_order_detail_rows(): void
    {
        $matcha = $this->matcha();
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        $detailsBefore = OrderItem::count();

        OrderItem::creating(function ($item) {
            throw new Exception("Simulated checkout failure");
        });

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Test User',
            'service_type'  => 'Takeout',
        ]);

        $this->assertSame($detailsBefore, OrderItem::count(), 'No orphaned order detail should remain.');
    }

    public function test_checkout_rollback_leaves_product_stock_unchanged(): void
    {
        $matcha = $this->matcha();
        $stockBefore = $matcha->quantity;
        
        $this->post(route('cart.add'), ['product_id' => $matcha->product_id, 'quantity' => 1]);

        OrderItem::creating(function ($item) {
            throw new Exception("Simulated checkout failure");
        });

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Test User',
            'service_type'  => 'Takeout',
        ]);

        $this->assertSame($stockBefore, $matcha->fresh()->quantity, 'Stock should be unchanged.');
    }
}
