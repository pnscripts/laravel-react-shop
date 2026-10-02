<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Navigation menus (header, footer, …) with nested items that link to pages, catalog
 * entries or any address. The header and footer menus are created empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('type', 32);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('label')->nullable();
            $table->boolean('new_tab')->default(false);
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'position']);
        });

        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->unique(['menu_item_id', 'locale']);
        });

        $now = now();
        DB::table('menus')->insert([
            ['code' => 'header', 'name' => 'Header', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'footer', 'name' => 'Footer', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_translations');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
