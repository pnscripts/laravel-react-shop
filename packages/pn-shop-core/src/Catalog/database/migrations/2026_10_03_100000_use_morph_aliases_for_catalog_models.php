<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catalog models moved from App\Models to PnShop\Catalog\Models and are now stored
 * under morph aliases, so polymorphic references survive future class moves.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $aliases = [
        'App\\Models\\Product' => 'product',
        'App\\Models\\ProductCategory' => 'category',
        'App\\Models\\ProductAttribute' => 'product_attribute',
        'App\\Models\\ProductAttributeValue' => 'product_attribute_value',
    ];

    public function up(): void
    {
        foreach ($this->aliases as $class => $alias) {
            DB::table('activity_log')->where('subject_type', $class)->update(['subject_type' => $alias]);
        }
    }

    public function down(): void
    {
        foreach ($this->aliases as $class => $alias) {
            DB::table('activity_log')->where('subject_type', $alias)->update(['subject_type' => $class]);
        }
    }
};
