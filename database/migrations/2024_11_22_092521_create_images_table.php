<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique(); // Tạo UID ngẫu nhiên
            $table->string('file_name');  // Tên file gốc
            $table->string('file_path'); // Đường dẫn file lưu trữ
            $table->timestamps();        // Thời gian tạo và cập nhật
        });
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
}
