<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An earlier version of the 2026_10_04 order totals backfill ignored a line's stored
 * sale price when it was above the regular price, while order lines (and what customers
 * were charged) use the stored sale price. Orders without adjustments whose subtotal
 * differs from their lines are corrected, together with the payment created for them
 * when payments were introduced. Databases migrated with the fixed backfill are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('totals', '[]')->orderBy('id')->select(['id', 'subtotal'])->chunkById(500, function ($orders) {
            $sums = [];

            foreach (DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get(['order_id', 'price', 'sale_price', 'quantity']) as $item) {
                $unit = $item->sale_price !== null && (int) $item->sale_price > 0 ? (int) $item->sale_price : (int) $item->price;
                $sums[$item->order_id] = ($sums[$item->order_id] ?? 0) + $unit * (int) $item->quantity;
            }

            foreach ($orders as $order) {
                $sum = $sums[$order->id] ?? 0;

                if ((int) $order->subtotal === $sum) {
                    continue;
                }

                DB::table('orders')->where('id', $order->id)->update(['subtotal' => $sum, 'total' => $sum]);

                $imported = DB::table('payment_transactions')->where('type', 'import')
                    ->whereIn('payment_id', DB::table('payments')->where('order_id', $order->id)->select('id'))
                    ->pluck('payment_id');

                DB::table('payments')->whereIn('id', $imported)->where('refunded_amount', 0)->update(['amount' => $sum]);
                DB::table('payment_transactions')->where('type', 'import')->whereIn('payment_id', $imported)->update(['amount' => $sum]);
            }
        });
    }

    public function down(): void
    {
        // Corrections are kept.
    }
};
