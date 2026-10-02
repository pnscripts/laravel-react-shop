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
        Schema::create('product_product_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // Explicit name: the generated one is longer than MySQL's 64-character limit.
            $table->foreignId('product_attribute_value_id')->constrained(indexName: 'ppav_product_attribute_value_id_foreign')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_value_id'], 'product_attribute_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_attribute_value');
    }
};
