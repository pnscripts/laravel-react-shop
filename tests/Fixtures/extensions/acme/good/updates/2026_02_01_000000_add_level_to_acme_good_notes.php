<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Copied into database/migrations by the update test, as version 1.1.0 would ship it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acme_good_notes', fn (Blueprint $table) => $table->string('level')->nullable());
    }

    public function down(): void
    {
        Schema::table('acme_good_notes', fn (Blueprint $table) => $table->dropColumn('level'));
    }
};
