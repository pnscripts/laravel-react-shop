<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Specification attributes can be marked filterable (shown as shop filters) and ordered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->boolean('is_filterable')->default(false)->after('is_required');
            $table->unsignedInteger('position')->default(0)->after('is_filterable');
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', fn (Blueprint $table) => $table->dropColumn('position'));
        Schema::table('product_attributes', fn (Blueprint $table) => $table->dropColumn(['is_filterable', 'position']));
    }
};
