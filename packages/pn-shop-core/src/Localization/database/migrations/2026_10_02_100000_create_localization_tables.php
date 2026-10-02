<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Languages, currencies and countries, with the reference data a store needs to run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name');
            $table->string('native_name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();
            $table->string('name');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('languages')->insert([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'bg', 'name' => 'Bulgarian', 'native_name' => 'Български', 'is_default' => false, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('currencies')->insert([
            ['code' => 'USD', 'name' => 'US Dollar', 'exchange_rate' => 1, 'is_default' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EUR', 'name' => 'Euro', 'exchange_rate' => 1, 'is_default' => false, 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $codes = require dirname(__DIR__).'/country-codes.php';

        DB::table('countries')->insert(array_map(
            fn (string $code) => ['code' => $code, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            $codes,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('languages');
    }
};
