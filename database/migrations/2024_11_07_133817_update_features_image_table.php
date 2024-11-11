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
                if (!Schema::hasColumn('features', 'model_id')) {
                    $table->string('model_id', 255)->after('description')->nullable();
                }
                if (!Schema::hasColumn('features', 'prompt')) {
                    $table->string('prompt', 255)->after('model_id')->nullable();
                }
                if (!Schema::hasColumn('features', 'presetStyle')) {
                    $table->string('presetStyle', 255)->after('prompt')->nullable();
                }
                if (!Schema::hasColumn('features', 'initImageId')) {
                    $table->string('initImageId', 255)->after('presetStyle')->nullable();
                }
                if (!Schema::hasColumn('features', 'preprocessorId')) {
                    $table->string('preprocessorId', 255)->after('presetStyle')->nullable();
                }
                if (!Schema::hasColumn('features', 'strengthType')) {
                    $table->string('strengthType', 255)->after('preprocessorId')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['model_id', 'prompt', 'presetStyle', 'initImageId', 'preprocessorId', 'strengthType']);
        });
    }
};
