<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Search engine title and description for products and categories, per language.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['products', 'product_translations', 'product_categories', 'product_category_translations'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['meta_title', 'meta_description']));
        }
    }
};
