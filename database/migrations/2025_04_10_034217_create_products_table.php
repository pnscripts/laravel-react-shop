<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');
            $table->string('title')->index(); // Name of the product
            $table->string('slug')->unique()->index(); // Slug for URL-friendly names
            $table->text('description')->nullable(); // Description of the product
            $table->decimal('price', 12, 2)->default(0); // Price of the product
            $table->decimal('discount_price', 12, 2)->nullable(); // Discounted price of the product
            $table->unsignedInteger('stock')->default(0); // Stock quantity of the product
            $table->boolean('is_active')->default(true)->index(); // Is the product active?
            $table->string('sku')->nullable(); // Stock Keeping Unit
            $table->string('barcode')->nullable(); // Barcode for the product
            $table->string('image')->nullable(); // Image URL or path
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_category_id', 'is_active']); // Index for product category and active status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
