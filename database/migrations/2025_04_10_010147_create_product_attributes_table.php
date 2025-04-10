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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Unique key like 'size', 'color'
            $table->string('label'); // Display label for translation (e.g., 'Size', 'Color')
            $table->string('type'); // Type of the attribute, such as 'text', 'select', 'boolean'
            $table->boolean('is_required')->default(false); // Whether this attribute is mandatory
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
