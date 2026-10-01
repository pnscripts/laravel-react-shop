<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redirects from old addresses: added by staff, or automatically when a slug changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 1024);
            $table->string('to_url', 2048);
            $table->unsignedSmallInteger('status')->default(301);
            $table->boolean('is_automatic')->default(false);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            $table->unique('from_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
