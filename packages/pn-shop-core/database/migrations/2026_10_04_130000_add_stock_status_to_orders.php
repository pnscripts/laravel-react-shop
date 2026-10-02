<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout now reserves stock instead of taking it; it leaves the shelf when the
 * order ships. Orders placed before this change already took their stock, so they
 * count as fulfilled (or released, when cancelled).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stock_status', 16)->default('fulfilled')->after('order_status_id');
        });

        $cancelled = DB::table('order_statuses')->where('name', 'cancelled')->pluck('id');

        if ($cancelled->isNotEmpty()) {
            DB::table('orders')->whereIn('order_status_id', $cancelled)->update(['stock_status' => 'released']);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_status');
        });
    }
};
