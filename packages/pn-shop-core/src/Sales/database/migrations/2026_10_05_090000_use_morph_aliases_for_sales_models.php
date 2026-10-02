<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Order models moved from App\Models to PnShop\Sales\Models and are now stored
 * under morph aliases, so polymorphic references survive future class moves.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $aliases = [
        'App\\Models\\Order' => 'order',
        'App\\Models\\PaymentMethod' => 'payment_method',
    ];

    /** @var array<string, string> table => type column */
    private array $columns = [
        'activity_log' => 'subject_type',
        'stock_movements' => 'reference_type',
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $column) {
            foreach ($this->aliases as $class => $alias) {
                DB::table($table)->where($column, $class)->update([$column => $alias]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $column) {
            foreach ($this->aliases as $class => $alias) {
                DB::table($table)->where($column, $alias)->update([$column => $class]);
            }
        }
    }
};
