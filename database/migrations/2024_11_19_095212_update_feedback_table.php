<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('feedback')) {
            DB::statement("ALTER TABLE `feedback` CHANGE COLUMN `flatfom` `platform` VARCHAR(255)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('feedback')) {
            DB::statement("ALTER TABLE `feedback` CHANGE COLUMN `platform` `flatfom` VARCHAR(255)");
        }
    }
};
