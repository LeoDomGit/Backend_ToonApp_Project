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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('key',255)->nullable();
            $table->string('en',255)->nullable();
            $table->string('vi',255)->nullable();
            $table->string('de',255)->nullable();
            $table->string('ksl',255)->nullable();
            $table->string('pl',255)->nullable();
            $table->string('nu',255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
