<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnshop_handling_fee_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pnshop_handling_fee_exemptions');
    }
};
