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
        if (Schema::hasTable('background')) {
            Schema::table('background', function (Blueprint $table) {
               if (!Schema::hasColumn('background', 'is_front')) {
                $table->unsignedBigInteger('is_front')->after('feature_id')->default(0);
               }
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
