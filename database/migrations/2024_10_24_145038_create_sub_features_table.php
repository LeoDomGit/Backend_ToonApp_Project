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
        Schema::create('sub_features', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->string('description',255)->nullable();
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('feature_id')->nullable();
            $table->timestamps();
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('SET NULL'); // Fixed here
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_features');
    }
};
