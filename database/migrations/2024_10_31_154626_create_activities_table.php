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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('photo_id');
            $table->unsignedBigInteger('features_id');
            $table->string('image_result',255);
            $table->unsignedBigInteger('image_size');
            $table->string('ai_model',255);
            $table->string('api_endpoint',255);
            $table->boolean('status')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->foreign('image_size')->references('id')->on('image_sizes')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('photo_id')->references('id')->on('photos')->onDelete('restrict');
            $table->foreign('features_id')->references('id')->on('features')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
