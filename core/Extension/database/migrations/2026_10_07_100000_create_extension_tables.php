<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Installed extensions with their state, and which migrations each one ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('name');
            $table->string('version', 64);
            $table->string('status', 16);
            $table->text('error')->nullable();
            $table->json('checksums')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('extension_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('extension_id', 191);
            $table->string('migration');
            $table->timestamp('created_at')->nullable();
            $table->unique(['extension_id', 'migration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_migrations');
        Schema::dropIfExists('extensions');
    }
};
