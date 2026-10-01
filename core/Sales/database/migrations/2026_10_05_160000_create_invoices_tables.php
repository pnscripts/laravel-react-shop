<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices keep a copy of everything printed on them (seller, buyer, lines, totals), so
 * later changes to the store or the order never change an issued invoice. Numbers come
 * from a locked counter, so they are sequential without gaps or duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('name', 64)->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number', 32)->unique();
            $table->timestamp('issued_at');
            $table->char('currency', 3);
            $table->string('locale', 12)->nullable();
            $table->json('seller');
            $table->json('buyer');
            $table->json('lines');
            $table->json('totals');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('number_sequences');
    }
};
