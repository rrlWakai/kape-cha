<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The printed menu now carries food and dessert alongside the drinks, so the
 * category enum has to accept them. Written as a follow-up migration so an
 * already-migrated database picks the new sections up too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('category', ['Coffee', 'Milk Tea', 'Food', 'Dessert'])
                ->default('Coffee')
                ->change();
        });
    }

    public function down(): void
    {
        // Food and dessert rows cannot be represented by the old enum, so the
        // down migration only succeeds once they are gone.
        DB::table('products')->whereNotIn('category', ['Coffee', 'Milk Tea'])->delete();

        Schema::table('products', function (Blueprint $table) {
            $table->enum('category', ['Coffee', 'Milk Tea'])
                ->default('Coffee')
                ->change();
        });
    }
};
