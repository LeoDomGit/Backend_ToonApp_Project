<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeServerVerificationDataTypeInSubscriptionHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_history', function (Blueprint $table) {
            $table->text('serverVerificationData')->change(); // Chỉnh sửa kiểu dữ liệu của trường serverVerificationData thành text
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_history', function (Blueprint $table) {
            $table->string('serverVerificationData')->change(); // Quay lại kiểu dữ liệu ban đầu (nếu cần)
        });
    }
}
