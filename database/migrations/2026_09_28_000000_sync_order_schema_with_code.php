<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings an already-migrated database back in line with the order schema the
 * application code actually uses.
 *
 * 2025_01_01_000000_create_products_orders_tables is recorded as run, but the
 * live MySQL database was built from an earlier revision of that file, so it
 * still exposes order_items / order_item_id and is missing the two columns the
 * ITST 302 spec adds to orders. The original migration must not be edited and
 * existing rows must survive, so this renames in place rather than dropping and
 * recreating the orders table.
 *
 * Every step is guarded, so against a database that is already correct — and
 * against the in-memory SQLite database the test suite builds by replaying the
 * migration files — this whole migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ITST 302 spec name for the order line table. Renaming the table keeps
        // its rows, its auto_increment counter and both of its foreign keys.
        if (Schema::hasTable('order_items') && ! Schema::hasTable('order_details')) {
            Schema::rename('order_items', 'order_details');
        }

        // Spec primary key name. The column is the auto_increment primary key,
        // so its full definition is restated rather than relying on a rename.
        if ($this->columnExists('order_details', 'order_item_id')
            && ! $this->columnExists('order_details', 'order_detail_id')) {
            $this->renamePrimaryKey('order_details', 'order_item_id', 'order_detail_id');
        }

        Schema::table('orders', function (Blueprint $table) {
            // Explicit timestamp written at checkout by CartController::checkout().
            // Nullable so pre-existing rows keep working once the column appears.
            if (! $this->columnExists('orders', 'order_date')) {
                $table->timestamp('order_date')->nullable();
            }

            // Nullable because customers may order without an account;
            // OrderProcessingService records the staff member on completion.
            if (! $this->columnExists('orders', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Reverse of up(). The constraint has to go first, because MySQL refuses
        // to drop a column that a foreign key still depends on.
        Schema::table('orders', function (Blueprint $table) {
            if ($this->columnExists('orders', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            if ($this->columnExists('orders', 'order_date')) {
                $table->dropColumn('order_date');
            }
        });

        if ($this->columnExists('order_details', 'order_detail_id')
            && ! $this->columnExists('order_details', 'order_item_id')) {
            $this->renamePrimaryKey('order_details', 'order_detail_id', 'order_item_id');
        }

        if (Schema::hasTable('order_details') && ! Schema::hasTable('order_items')) {
            Schema::rename('order_details', 'order_items');
        }
    }

    /**
     * Does the column exist, without relying on the framework's own lookup?
     *
     * Schema::hasColumn() reads information_schema.columns.generation_expression,
     * a field that only exists from MySQL 5.7 onwards, so it throws on the
     * MySQL 5.6 server this project is developed against.
     */
    private function columnExists(string $table, string $column): bool
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return Schema::hasColumn($table, $column);
        }

        $row = DB::selectOne(
            'select count(*) as aggregate from information_schema.columns
             where table_schema = schema() and table_name = ? and column_name = ?',
            [$table, $column]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }

    /**
     * Rename the order line primary key, preserving its type and auto_increment.
     *
     * For the same 5.6 reason Schema::renameColumn() is avoided: on MySQL older
     * than 8.0.3 it has to rebuild the definition from the same unavailable
     * information_schema column. The CHANGE clause below is what it would emit.
     */
    private function renamePrimaryKey(string $table, string $from, string $to): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
                $blueprint->renameColumn($from, $to);
            });

            return;
        }

        DB::statement(sprintf(
            'alter table `%s` change `%s` `%s` int(10) unsigned not null auto_increment',
            $table,
            $from,
            $to
        ));
    }
};
