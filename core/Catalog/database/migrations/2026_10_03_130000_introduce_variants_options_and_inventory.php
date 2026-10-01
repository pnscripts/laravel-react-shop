<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every product gets at least one variant. SKU, barcode, price, sale price and stock move
 * from products to product_variants (stock into the inventory tables); variable products
 * combine option values (Size: M, Color: Red). Existing products become one default variant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('name')->nullable();
            $table->timestamps();
            $table->unique(['option_id', 'locale']);
        });

        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['option_id', 'position']);
        });

        Schema::create('option_value_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_value_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['option_value_id', 'locale']);
        });

        Schema::create('option_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['product_id', 'option_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 32)->default('simple')->after('id');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable();
            $table->bigInteger('price')->default(0);
            $table->bigInteger('sale_price')->nullable();
            $table->unsignedInteger('weight')->nullable()->comment('grams');
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'is_default']);
        });

        Schema::create('option_value_product_variant', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained()->restrictOnDelete();
            $table->primary(['product_variant_id', 'option_value_id']);
        });

        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained()->cascadeOnDelete();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->timestamps();
            $table->unique(['product_variant_id', 'stock_location_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->comment('positive in, negative out');
            $table->integer('on_hand_after');
            $table->string('reason', 32);
            $table->nullableMorphs('reference');
            $table->foreignId('admin_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_variant_id', 'created_at']);
        });

        $now = now();
        $locationId = DB::table('stock_locations')->insertGetId([
            'code' => 'default', 'name' => 'Main warehouse', 'is_default' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach (DB::table('products')->orderBy('id')->get(['id', 'sku', 'barcode', 'price', 'discount_price', 'stock']) as $product) {
            $variantId = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'sku' => $product->sku !== null && $product->sku !== '' && ! DB::table('product_variants')->where('sku', $product->sku)->exists() ? $product->sku : null,
                'barcode' => $product->barcode,
                'price' => (int) $product->price,
                'sale_price' => $product->discount_price,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('stock_levels')->insert([
                'product_variant_id' => $variantId, 'stock_location_id' => $locationId, 'on_hand' => (int) $product->stock, 'reserved' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);

            DB::table('stock_movements')->insert([
                'product_variant_id' => $variantId, 'stock_location_id' => $locationId, 'quantity' => (int) $product->stock, 'on_hand_after' => (int) $product->stock,
                'reason' => 'initial', 'note' => 'Opening balance when variants were introduced', 'created_at' => $now,
            ]);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('variant_label')->nullable()->after('product_sku');
            $table->renameColumn('discount_price', 'sale_price');
        });

        DB::table('order_items')->whereNotNull('product_id')->update([
            'product_variant_id' => DB::raw('(select id from product_variants where product_variants.product_id = order_items.product_id and is_default = true limit 1)'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'barcode', 'price', 'discount_price', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->bigInteger('price')->nullable();
            $table->bigInteger('discount_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
        });

        foreach (DB::table('product_variants')->where('is_default', true)->get() as $variant) {
            DB::table('products')->where('id', $variant->product_id)->update([
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price' => $variant->price,
                'discount_price' => $variant->sale_price,
                'stock' => (int) DB::table('stock_levels')->where('product_variant_id', $variant->id)->sum('on_hand'),
            ]);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_label');
            $table->renameColumn('sale_price', 'discount_price');
        });

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('type'));

        foreach (['stock_movements', 'stock_levels', 'stock_locations', 'option_value_product_variant', 'product_variants', 'option_product', 'option_value_translations', 'option_values', 'option_translations', 'options'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
