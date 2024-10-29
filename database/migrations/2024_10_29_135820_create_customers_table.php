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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); 
            $table->string('username',255);
            $table->string('password',255)->nullable();
            $table->string('fullname',255)->nullable();
            $table->string('email',255)->nullable();
            $table->integer('age')->nullable(); 
            $table->string('gender',255)->nullable();
            $table->string('remember_token',255)->nullable();
            $table->string('place_of_birth',255)->nullable();
            $table->string('country',255)->nullable();
            $table->string('city',255)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
