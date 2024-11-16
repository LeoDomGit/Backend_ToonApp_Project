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
        Schema::table('background', function (Blueprint $table) {
            if (!Schema::hasColumn('background', 'group_id')) {
                $table->unsignedBigInteger('group_id');
                $table->foreign('group_id')->references('id')->on('group_backgrounds');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
     
    }
};
