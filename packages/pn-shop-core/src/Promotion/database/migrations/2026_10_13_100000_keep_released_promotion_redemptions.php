<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A cancelled order's redemptions are kept (marked released) instead of deleted, so
 * reopening the order can take the uses back within the usage limits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_redemptions', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_redemptions', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });
    }
};
