<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (Schema::getForeignKeys('apparel_order_items') as $foreignKey) {
            if ($foreignKey['columns'] !== ['apparel_order_id']
                || $foreignKey['foreign_table'] !== 'apparel_orders_backup') {
                continue;
            }

            $hasOrphans = DB::table('apparel_order_items as items')
                ->leftJoin('apparel_orders as orders', 'orders.id', '=', 'items.apparel_order_id')
                ->whereNull('orders.id')->exists();

            if ($hasOrphans) {
                throw new RuntimeException('Cannot repair apparel order foreign key: some items have no matching apparel_orders row. Reconcile backup orders and their items before rerunning this migration.');
            }

            // One ALTER keeps the replacement together; do not disable FK checks.
            $constraint = str_replace('`', '``', $foreignKey['name']);
            $table = str_replace('`', '``', DB::getTablePrefix().'apparel_order_items');
            $orders = str_replace('`', '``', DB::getTablePrefix().'apparel_orders');
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`, ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`apparel_order_id`) REFERENCES `{$orders}` (`id`) ON DELETE CASCADE");
        }
    }

    public function down(): void
    {
        // A rollback must not restore the invalid reference to the backup table.
    }
};
