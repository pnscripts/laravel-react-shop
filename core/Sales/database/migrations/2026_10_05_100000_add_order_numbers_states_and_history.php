<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders get a human number, three independent states (status, payment, fulfillment)
 * and a history. The editable `order_statuses` list is replaced by the fixed states:
 *
 *   pending   → pending,    unpaid, unfulfilled
 *   paid      → processing, paid,   unfulfilled
 *   shipped   → processing, unpaid, fulfilled   (payment is unknown; staff confirm it)
 *   cancelled → cancelled,  unpaid, unfulfilled
 *   other     → pending,    unpaid, unfulfilled
 */
return new class extends Migration
{
    /** @var array<string, array{string, string, string}> */
    private array $map = [
        'pending' => ['pending', 'unpaid', 'unfulfilled'],
        'paid' => ['processing', 'paid', 'unfulfilled'],
        'shipped' => ['processing', 'unpaid', 'fulfilled'],
        'cancelled' => ['cancelled', 'unpaid', 'unfulfilled'],
    ];

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('number', 32)->nullable()->unique()->after('id');
            $table->string('status', 20)->default('pending')->index()->after('user_id');
            $table->string('payment_status', 24)->default('unpaid')->index()->after('status');
            $table->string('fulfillment_status', 24)->default('unfulfilled')->index()->after('payment_status');
            $table->string('locale', 12)->nullable()->after('currency');
        });

        Schema::create('order_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('field', 24);
            $table->string('from', 24)->nullable();
            $table->string('to', 24)->nullable();
            $table->text('note')->nullable();
            $table->nullableMorphs('actor');
            $table->timestamp('created_at')->nullable();
            $table->index(['order_id', 'id']);
        });

        $names = Schema::hasTable('order_statuses') ? DB::table('order_statuses')->pluck('name', 'id') : collect();

        DB::table('orders')->orderBy('id')->select(['id', 'order_status_id', 'created_at'])->chunkById(500, function ($orders) use ($names) {
            foreach ($orders as $order) {
                [$status, $payment, $fulfillment] = $this->map[strtolower((string) ($names[$order->order_status_id] ?? ''))] ?? $this->map['pending'];

                DB::table('orders')->where('id', $order->id)->update([
                    'number' => 'ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'payment_status' => $payment,
                    'fulfillment_status' => $fulfillment,
                ]);

                DB::table('order_history')->insert([
                    'order_id' => $order->id,
                    'field' => 'status',
                    'from' => null,
                    'to' => $status,
                    'note' => 'Imported from the previous order status list',
                    'created_at' => $order->created_at,
                ]);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_status_id');
        });

        Schema::dropIfExists('order_statuses');
    }

    public function down(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        foreach (['pending', 'paid', 'shipped', 'cancelled'] as $name) {
            DB::table('order_statuses')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_status_id')->nullable()->constrained('order_statuses')->nullOnDelete();
        });

        $ids = DB::table('order_statuses')->pluck('id', 'name');

        foreach (DB::table('orders')->get(['id', 'status', 'payment_status', 'fulfillment_status']) as $order) {
            $name = match (true) {
                $order->status === 'cancelled' => 'cancelled',
                $order->fulfillment_status !== 'unfulfilled' => 'shipped',
                $order->payment_status === 'paid' => 'paid',
                default => 'pending',
            };

            DB::table('orders')->where('id', $order->id)->update(['order_status_id' => $ids[$name]]);
        }

        Schema::dropIfExists('order_history');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['number', 'status', 'payment_status', 'fulfillment_status', 'locale']);
        });
    }
};
