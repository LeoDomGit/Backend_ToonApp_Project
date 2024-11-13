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
        if (Schema::hasTable('sub_features')) {
            Schema::table('sub_features', function (Blueprint $table) {
                $table->string('model_id',255);
                $table->string('presetStyle',255);
                $table->string('preprocessorId',255);
                $table->string('strengthType',255);
                $table->string('initImageId',255);
                $table->boolean('is_pro')->default(0);
                $table->boolean('detech_face')->default(0);
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
