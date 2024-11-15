<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('auth_provider',255)->after('device_id')->nullable();
                $table->string('auth_provider_id',255)->after('device_id')->nullable();
                $table->string('auth_email',255)->after('device_id')->nullable();
                $table->string('auth_token',255)->after('device_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
