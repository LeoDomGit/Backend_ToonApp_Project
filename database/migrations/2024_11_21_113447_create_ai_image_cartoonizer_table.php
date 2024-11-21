<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiImageCartoonizerTable extends Migration
{
    public function up()
    {
        Schema::create('ai_image_cartoonizer', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('img2anime');
            $table->string('module')->default('img2anime');
            $table->string('model_name');
            $table->string('prompt')->nullable();
            $table->boolean('overwrite')->default(false);
            $table->float('denoising_strength')->default(0.75);
            $table->string('image_uid');
            $table->string('cn_name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_image_cartoonizer');
    }
}
