<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 64);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum', 64)->index();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->json('conversions')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });

        Schema::create('media_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'locale']);
        });

        Schema::create('mediables', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('collection', 64);
            $table->unsignedInteger('position')->default(0);

            $table->primary(['media_id', 'mediable_type', 'mediable_id', 'collection']);
            $table->index(['mediable_type', 'mediable_id', 'collection', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media_translations');
        Schema::dropIfExists('media');
    }
};
