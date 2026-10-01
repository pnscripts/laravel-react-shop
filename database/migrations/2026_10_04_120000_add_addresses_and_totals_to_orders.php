<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders get structured shipping and billing addresses (copies, so later address
 * book edits never change an order) and stored totals.
 *
 * Existing orders keep their free-text `address`, which cannot be split into fields
 * reliably; their subtotal and total are filled from their lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('company', 150)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 100);
            $table->string('postcode', 32)->nullable();
            $table->string('region', 100)->nullable();
            $table->char('country_code', 2);
            $table->string('phone', 50)->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->bigInteger('subtotal')->nullable()->after('currency');
            $table->bigInteger('total')->nullable()->after('subtotal');
            $table->json('totals')->nullable()->after('total');
        });

        DB::table('orders')->orderBy('id')->select('id')->chunkById(500, function ($orders) {
            $sums = [];

            foreach (DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get(['order_id', 'price', 'sale_price', 'quantity']) as $item) {
                $unit = $item->sale_price !== null && (int) $item->sale_price < (int) $item->price ? (int) $item->sale_price : (int) $item->price;
                $sums[$item->order_id] = ($sums[$item->order_id] ?? 0) + $unit * (int) $item->quantity;
            }

            foreach ($orders as $order) {
                $sum = $sums[$order->id] ?? 0;
                DB::table('orders')->where('id', $order->id)->update(['subtotal' => $sum, 'total' => $sum, 'totals' => '[]']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'total', 'totals']);
        });

        Schema::dropIfExists('order_addresses');
    }
};
