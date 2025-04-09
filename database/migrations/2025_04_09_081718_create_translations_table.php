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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable'); // Polymorphic relation (model_id, model_type)
            $table->string('field'); // e.g., title, description
            $table->string('locale', 5); // e.g., en, fr, de
            $table->text('value'); // Translated content
            $table->timestamps();
            $table->softDeletes(); // This adds a nullable deleted_at column

            $table->unique(['translatable_id', 'translatable_type', 'field', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
