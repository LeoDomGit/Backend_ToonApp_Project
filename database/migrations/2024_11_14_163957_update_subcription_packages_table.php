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
        if (Schema::hasTable('subcription_packages')) {
            Schema::table('subcription_packages', function(Blueprint $table){
                $table->varchar('product_id_and', 255)->after('description')->nullable();
                $table->varchar('product_id_ios', 255)->after('description')->nullable();
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
