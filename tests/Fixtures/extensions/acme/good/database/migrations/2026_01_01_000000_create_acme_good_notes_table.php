<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_good_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_good_notes');
    }
};
