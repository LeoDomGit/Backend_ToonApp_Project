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
        if (Schema::hasTable('features')) {
            Schema::table('features', function (Blueprint $table) {
                $table->bigInteger('weight')->after('strengthType')->nullable();
            });
        }
        if (Schema::hasTable('sub_features')) {
            Schema::table('sub_features', function (Blueprint $table) {
                $table->bigInteger('weight')->after('strengthType')->nullable();
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
