<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shipping zones and methods, shipments with their lines, the chosen shipping method
 * on orders and how much of each order line has shipped. Lines of orders whose stock
 * already left the shelf count as shipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('countries')->nullable();
            $table->json('postcodes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('carrier', 64);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipping_method_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['shipping_method_id', 'locale']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
            $table->string('shipping_method_name')->nullable()->after('shipping_method_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity_fulfilled')->default(0)->after('quantity');
        });

        $shipped = DB::table('orders')->where('stock_status', 'fulfilled')->pluck('id');

        foreach ($shipped->chunk(500) as $ids) {
            DB::table('order_items')->whereIn('order_id', $ids)->update(['quantity_fulfilled' => DB::raw('quantity')]);
        }

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('carrier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->nullableMorphs('actor');
            $table->timestamps();
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
        Schema::dropIfExists('shipments');

        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('quantity_fulfilled'));

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn('shipping_method_name');
        });

        Schema::dropIfExists('shipping_method_translations');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
    }
};
