<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_relations', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 16)->comment('related, upsell or cross_sell');
            $table->unsignedInteger('position')->default(0);

            $table->primary(['product_id', 'related_product_id', 'type']);
            $table->index(['product_id', 'type', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
    }
};
