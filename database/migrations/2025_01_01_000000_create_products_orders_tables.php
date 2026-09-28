<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // products table (ITST 302 spec: product_id, product_name, price, quantity)
        Schema::create('products', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->autoIncrement()->comment('product_id');
            $table->string('product_name', 191)->unique();
            $table->enum('category', ['Coffee', 'Milk Tea'])->default('Coffee');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // orders — header + status columns consumed by the Phase 2 ACID engine.
        // ITST 302 spec requires: order_id, user_id, order_date, total_amount
        Schema::create('orders', function (Blueprint $table) {
            $table->unsignedInteger('order_id')->autoIncrement()->comment('order_id');
            $table->string('order_number', 20)->unique();
            $table->string('customer_name', 100)->nullable();
            $table->enum('service_type', ['Dine-in', 'Takeout'])->default('Takeout');
            $table->enum('status', ['Pending', 'Processing', 'Completed', 'Cancelled'])->default('Pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            // ITST 302 spec: user_id — nullable because customers are public (no account required).
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            // ITST 302 spec: order_date — explicit timestamp populated at checkout.
            $table->timestamp('order_date')->nullable();
            // processed_by records the Staff/Admin who ran the ACID processing transaction.
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        // order_details (ITST 302 spec name) — persisted by checkout, consumed by the ACID engine.
        // Previously named order_items / order_item_id — renamed for spec compliance.
        Schema::create('order_details', function (Blueprint $table) {
            $table->unsignedInteger('order_detail_id')->autoIncrement()->comment('order_detail_id');
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('order_id')->on('orders')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            // restrictOnDelete: deleting a product is blocked while order_details still reference it,
            // protecting historical order records from silent erasure.
            $table->foreign('product_id')->references('product_id')->on('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
