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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index(); // Name of the type (e.g., electronics, clothing, books)
            $table->string('slug')->unique()->index(); // Slug for URL-friendly names
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->onDelete('cascade'); // Hierarchy support
            $table->unsignedInteger('sort_order')->default(0)->index(); // Sort order for display
            $table->boolean('is_active')->default(true)->index(); // Active status for the category
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
