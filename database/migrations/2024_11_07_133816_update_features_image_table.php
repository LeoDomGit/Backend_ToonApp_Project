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
                $table->string('model_id',255)->after('description')->nullable();
                $table->string('prompt',255)->after('model_id')->nullable();
                $table->string('presetStyle',255)->after('prompt')->nullable();
                $table->string('initImageId',255)->after('presetStyle')->nullable();
                $table->string('preprocessorId',255)->after('presetStyle')->nullable();
                $table->string('strengthType',255)->after('preprocessorId')->nullable();
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
