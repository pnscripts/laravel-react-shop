<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PnShop\Foundation\PnShop;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 50);
            $table->string('from_version', 50)->nullable();
            // install | update | adopt (a shop set up before the installer existed)
            $table->string('action', 20);
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // An existing shop (it has staff accounts) is recorded as installed, so the web
        // installer never opens on a running store.
        if (Schema::hasTable('admin_users') && DB::table('admin_users')->exists()) {
            DB::table('system_versions')->insert(['version' => PnShop::VERSION, 'action' => 'adopt', 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_versions');
    }
};
