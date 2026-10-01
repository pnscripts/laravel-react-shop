<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refunds of an order's payments, with the lines and quantities they cover. Order lines
 * count refunded units, and refunded units that had not shipped (they will not ship).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->char('currency', 3);
            $table->bigInteger('amount');
            $table->string('status', 16);
            $table->boolean('restock')->default(false);
            $table->text('reason')->nullable();
            $table->string('reference')->nullable();
            $table->nullableMorphs('actor');
            $table->timestamps();
        });

        Schema::create('refund_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->bigInteger('amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity_refunded')->default(0)->after('quantity_fulfilled');
            // Refunded before they shipped: they will not ship.
            $table->unsignedInteger('quantity_cancelled')->default(0)->after('quantity_refunded');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['quantity_refunded', 'quantity_cancelled']));
        Schema::dropIfExists('refund_lines');
        Schema::dropIfExists('refunds');
    }
};
