<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment methods choose a gateway (the old free-text `type` becomes `gateway`) and get
 * settings, ordering, availability rules and translations. Payments and their
 * transactions are recorded per order; every existing order gets a payment row that
 * matches its payment state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->renameColumn('type', 'gateway');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->json('settings')->nullable()->after('gateway');
            $table->unsignedInteger('position')->default(0)->after('is_active');
            $table->bigInteger('min_total')->nullable()->after('position');
            $table->bigInteger('max_total')->nullable()->after('min_total');
            $table->json('countries')->nullable()->after('max_total');
        });

        Schema::create('payment_method_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['payment_method_id', 'locale']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 64);
            $table->string('status', 24)->index();
            $table->char('currency', 3);
            $table->bigInteger('amount');
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('reference')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('outcome', 24);
            $table->char('currency', 3);
            $table->bigInteger('amount')->nullable();
            $table->string('reference')->nullable();
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->nullableMorphs('actor');
            $table->timestamp('created_at')->nullable();
        });

        // Methods whose type is not a built-in gateway keep it; they stay hidden until a gateway with that code is installed.
        DB::table('payment_methods')->where('gateway', 'cod')->update(['gateway' => 'cash_on_delivery']);

        $states = ['paid' => 'paid', 'authorized' => 'authorized', 'failed' => 'failed', 'refunded' => 'refunded', 'partially_refunded' => 'partially_refunded'];
        $gateways = DB::table('payment_methods')->pluck('gateway', 'id');

        DB::table('orders')->orderBy('id')->select(['id', 'payment_method_id', 'payment_status', 'status', 'currency', 'total', 'created_at'])
            ->chunkById(500, function ($orders) use ($states, $gateways) {
                foreach ($orders as $order) {
                    $status = $states[$order->payment_status] ?? ($order->status === 'cancelled' ? 'cancelled' : 'pending');
                    $amount = (int) $order->total;

                    $paymentId = DB::table('payments')->insertGetId([
                        'order_id' => $order->id,
                        'payment_method_id' => $order->payment_method_id,
                        'gateway' => $gateways[$order->payment_method_id] ?? 'manual',
                        'status' => $status,
                        'currency' => $order->currency,
                        'amount' => $amount,
                        'refunded_amount' => $status === 'refunded' ? $amount : 0,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->created_at,
                    ]);

                    DB::table('payment_transactions')->insert([
                        'payment_id' => $paymentId,
                        'type' => 'import',
                        'outcome' => $status,
                        'currency' => $order->currency,
                        'amount' => $amount,
                        'message' => 'Created from the order when payments were introduced',
                        'created_at' => $order->created_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_method_translations');

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['settings', 'position', 'min_total', 'max_total', 'countries']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->renameColumn('gateway', 'type');
        });
    }
};
