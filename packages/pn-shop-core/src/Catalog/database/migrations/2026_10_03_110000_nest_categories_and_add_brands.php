<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PnShop\Catalog\Models\Category;

/**
 * - Categories become a nested set (fast subtree queries); the tree is rebuilt from parent_id.
 * - Products can belong to several categories; product_category_id stays as the primary
 *   category (breadcrumbs, canonical URL) and is copied into the new pivot.
 * - Brands, with translations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('_lft')->default(0);
            $table->unsignedBigInteger('_rgt')->default(0);
            $table->text('description')->nullable();
            $table->index(['_lft', '_rgt', 'parent_id']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('product_category_translations', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        Category::query()->withoutGlobalScopes()->getQuery()->update(['_lft' => 0, '_rgt' => 0]);
        Category::fixTree();

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->primary(['product_id', 'product_category_id']);
            $table->index('product_category_id');
        });

        DB::table('category_product')->insertUsing(
            ['product_id', 'product_category_id'],
            DB::table('products')->whereNotNull('product_category_id')->select('id', 'product_category_id'),
        );

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brand_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('product_category_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });

        Schema::dropIfExists('brand_translations');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('category_product');

        Schema::table('product_category_translations', fn (Blueprint $table) => $table->dropColumn('description'));

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex(['_lft', '_rgt', 'parent_id']);
            $table->dropColumn(['_lft', '_rgt', 'description']);
            $table->unsignedInteger('sort_order')->default(0)->index();
        });
    }
};
