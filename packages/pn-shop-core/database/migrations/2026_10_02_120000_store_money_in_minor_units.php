<?php

use Brick\Money\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prices become integers in minor units of the store currency (12.50 USD => 1250),
 * and orders record the currency they were placed in.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $columns = [
        'products' => ['price', 'discount_price'],
        'order_items' => ['price', 'discount_price'],
    ];

    public function up(): void
    {
        $currency = DB::table('currencies')->where('is_default', true)->value('code') ?? 'USD';
        $factor = 10 ** Currency::of($currency)->getDefaultFractionDigits();

        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->bigInteger("{$column}_minor")->nullable();
                }
            });

            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->update([
                    "{$column}_minor" => DB::raw("ROUND({$column} * {$factor})"),
                ]);
            }

            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($columns));

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->renameColumn("{$column}_minor", $column);
                }
            });
        }

        DB::table('products')->whereNull('price')->update(['price' => 0]);
        DB::table('order_items')->where('discount_price', 0)->update(['discount_price' => null]);

        foreach (['orders', 'order_items'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->char('currency', 3)->default($currency));
        }
    }

    public function down(): void
    {
        $factor = 10 ** Currency::of(DB::table('currencies')->where('is_default', true)->value('code') ?? 'USD')->getDefaultFractionDigits();

        foreach (['orders', 'order_items'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('currency'));
        }

        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->decimal("{$column}_decimal", 15, 2)->nullable();
                }
            });

            foreach ($columns as $column) {
                DB::table($table)->update(["{$column}_decimal" => DB::raw("{$column} / {$factor}.0")]);
            }

            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($columns));

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->renameColumn("{$column}_decimal", $column);
                }
            });
        }
    }
};
