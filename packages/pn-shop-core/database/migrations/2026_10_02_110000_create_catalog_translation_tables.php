<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Replaces the polymorphic one-row-per-field "translations" table with one translation
 * table per entity (one row per locale). Existing translations are carried over; values
 * stored for the default language are dropped because the entity columns hold them.
 */
return new class extends Migration
{
    /** @var array<string, array{table: string, foreign: string, columns: array<string, string>}> */
    private array $entities = [
        'App\\Models\\Product' => ['table' => 'products', 'foreign' => 'product_id', 'columns' => ['title' => 'string', 'slug' => 'string', 'description' => 'text']],
        'App\\Models\\ProductCategory' => ['table' => 'product_categories', 'foreign' => 'product_category_id', 'columns' => ['title' => 'string', 'slug' => 'string']],
        'App\\Models\\ProductAttribute' => ['table' => 'product_attributes', 'foreign' => 'product_attribute_id', 'columns' => ['label' => 'string']],
        'App\\Models\\ProductAttributeValue' => ['table' => 'product_attribute_values', 'foreign' => 'product_attribute_value_id', 'columns' => ['value' => 'string']],
    ];

    /**
     * Laravel's index name, shortened when it is over MySQL's 64-character limit.
     *
     * @param  list<string>  $columns
     */
    private function indexName(string $table, array $columns, string $type): string
    {
        $name = strtolower($table.'_'.implode('_', $columns).'_'.$type);

        return strlen($name) <= 64 ? $name : substr($name, 0, 55).'_'.substr(md5($name), 0, 8);
    }

    public function up(): void
    {
        foreach ($this->entities as $entity) {
            Schema::create($this->translationTable($entity['table']), function (Blueprint $table) use ($entity) {
                $table->id();
                $translations = $this->translationTable($entity['table']);
                $table->foreignId($entity['foreign'])->constrained($entity['table'], indexName: $this->indexName($translations, [$entity['foreign']], 'foreign'))->cascadeOnDelete();
                $table->string('locale', 12);

                foreach ($entity['columns'] as $column => $type) {
                    $type === 'text' ? $table->text($column)->nullable() : $table->string($column)->nullable();
                }

                $table->timestamps();

                $table->unique([$entity['foreign'], 'locale'], $this->indexName($translations, [$entity['foreign'], 'locale'], 'unique'));

                if (isset($entity['columns']['slug'])) {
                    $table->unique(['locale', 'slug']);
                }
            });
        }

        $this->copyLegacyTranslations();

        Schema::dropIfExists('translations');
    }

    public function down(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('field');
            $table->string('locale', 5);
            $table->text('value');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['translatable_id', 'translatable_type', 'field', 'locale']);
        });

        foreach ($this->entities as $entity) {
            Schema::dropIfExists($this->translationTable($entity['table']));
        }
    }

    private function translationTable(string $table): string
    {
        return Str::singular($table).'_translations';
    }

    private function copyLegacyTranslations(): void
    {
        if (! Schema::hasTable('translations')) {
            return;
        }

        $default = DB::table('languages')->where('is_default', true)->value('code') ?? config('app.locale');
        $now = now();

        foreach ($this->entities as $type => $entity) {
            $rows = [];

            foreach (DB::table('translations')->where('translatable_type', $type)->whereNull('deleted_at')->where('locale', '!=', $default)->get() as $translation) {
                if (! array_key_exists($translation->field, $entity['columns'])) {
                    continue;
                }

                $key = $translation->translatable_id.'|'.$translation->locale;
                $rows[$key] ??= [$entity['foreign'] => $translation->translatable_id, 'locale' => $translation->locale, 'created_at' => $now, 'updated_at' => $now]
                    + array_fill_keys(array_keys($entity['columns']), null);
                $rows[$key][$translation->field] = $translation->value;
            }

            $existing = DB::table($entity['table'])->pluck('id')->flip();

            foreach (array_chunk(array_values(array_filter($rows, fn (array $row) => $existing->has($row[$entity['foreign']]))), 500) as $chunk) {
                DB::table($this->translationTable($entity['table']))->insert($chunk);
            }
        }
    }
};
