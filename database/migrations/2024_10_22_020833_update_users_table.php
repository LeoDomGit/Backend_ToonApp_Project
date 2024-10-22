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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->after('email')->nullable();
                $table->boolean('status')->default(1);
                $table->foreign('role_id')->references('id')->on('roles');
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
