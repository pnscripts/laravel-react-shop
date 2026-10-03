<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the lookups the shop makes all the time: a customer's orders, an order's
 * payments, shipments, refunds and returns, newest-first listings, the unpaid-order job.
 *
 * MySQL already indexes every foreign key; PostgreSQL and SQLite do not. An index that
 * already exists (under any name) is skipped, so nothing is duplicated.
 */
return new class extends Migration
{
    /** @var array<string, list<list<string>>> table => column lists */
    private const INDEXES = [
        'orders' => [['user_id'], ['created_at'], ['status', 'payment_status', 'created_at']],
        'order_items' => [['product_id'], ['product_variant_id']],
        'products' => [['created_at'], ['brand_id']],
        'product_variants' => [['barcode']],
        'product_relations' => [['related_product_id']],
        'cart_lines' => [['product_variant_id']],
        'customer_addresses' => [['user_id']],
        'payments' => [['order_id']],
        'payment_transactions' => [['payment_id']],
        'refunds' => [['order_id']],
        'refund_lines' => [['refund_id']],
        'shipments' => [['order_id']],
        'shipment_lines' => [['shipment_id']],
        'return_requests' => [['order_id'], ['user_id']],
        'return_request_lines' => [['return_request_id']],
        'promotion_redemptions' => [['order_id'], ['user_id']],
        'coupons' => [['promotion_id']],
        'page_revisions' => [['page_id']],
        'menu_items' => [['parent_id']],
        'shipping_methods' => [['shipping_zone_id']],
        'tax_rates' => [['tax_zone_id']],
        'stock_levels' => [['stock_location_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns) || $this->hasLeadingIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                $name = $this->name($table, $columns);

                if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    /**
     * Whether an index (or unique key, or primary key) already starts with these columns.
     *
     * @param  list<string>  $columns
     */
    private function hasLeadingIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function name(string $table, array $columns): string
    {
        return substr('pnshop_'.$table.'_'.implode('_', $columns).'_index', 0, 64);
    }
};
