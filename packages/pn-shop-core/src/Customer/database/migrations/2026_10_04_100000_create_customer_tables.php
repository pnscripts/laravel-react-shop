<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer accounts are the `users` table (staff have their own admin_users table).
 * Customers belong to a group (pricing and tax rules later) and keep an address book.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('customer_groups')->insert(['code' => 'retail', 'name' => 'Retail', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->after('password')->constrained()->nullOnDelete();
            $table->string('phone', 50)->nullable()->after('email');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('postcode', 32)->nullable();
            $table->string('region')->nullable();
            $table->char('country_code', 2);
            $table->string('phone', 50)->nullable();
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_group_id');
            $table->dropColumn('phone');
        });

        Schema::dropIfExists('customer_groups');
    }
};
