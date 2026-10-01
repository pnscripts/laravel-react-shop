<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tax classes (what is taxed how), tax zones (where) and rates (how much). Products
 * and shipping methods get a tax class; order lines keep the tax charged on them.
 * Without rates nothing is taxed, so existing stores behave as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('countries')->nullable();
            $table->json('postcodes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_compound')->default(false);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tax_class_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->foreignId('tax_class_id')->nullable()->after('carrier')->constrained()->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->bigInteger('tax_amount')->default(0)->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('tax_amount'));
        Schema::table('shipping_methods', fn (Blueprint $table) => $table->dropConstrainedForeignId('tax_class_id'));
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('tax_class_id'));

        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_zones');
        Schema::dropIfExists('tax_classes');
    }
};
