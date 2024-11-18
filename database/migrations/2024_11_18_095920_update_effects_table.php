<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('effects')) {
            Schema::table('effects', function (Blueprint $table) {
                $table->string('slug', 255)->after('name')->nullable();
                $table->string('image', 255)->after('slug')->nullable();
                $table->boolean('status')->after('image')->default(false);
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
