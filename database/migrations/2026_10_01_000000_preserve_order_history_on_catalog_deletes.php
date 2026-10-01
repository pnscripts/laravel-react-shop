<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Order lines keep a snapshot of the product title and SKU, and deleting a
 * product or category can no longer cascade into order history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_title')->nullable()->after('product_id');
            $table->string('product_sku')->nullable()->after('product_title');
        });

        DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select('order_items.id', 'products.title', 'products.sku')
            ->orderBy('order_items.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('order_items')->where('id', $row->id)->update([
                        'product_title' => $row->title,
                        'product_sku' => $row->sku,
                    ]);
                }
            });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index('order_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->foreign('product_category_id')->references('id')->on('product_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->foreign('product_category_id')->references('id')->on('product_categories')->cascadeOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->dropColumn(['product_title', 'product_sku']);
        });
    }
};
