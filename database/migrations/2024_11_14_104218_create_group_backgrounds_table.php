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
        Schema::create('group_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('feature_id')->nullable();
            $table->unsignedBigInteger('sub_feature_id')->nullable();
            $table->timestamps();
            $table->foreign('feature_id')->references('id')->on('features');
            $table->foreign('sub_feature_id')->references('id')->on('sub_features');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_backgrounds');
    }
};
