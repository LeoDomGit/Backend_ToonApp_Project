<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiKeyToAiImageCartoonizer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_image_cartoonizer', function (Blueprint $table) {
            // Add the apiKey column to the table
            $table->string('apiKey')->nullable()->after('cn_name'); // You can adjust the column type and position as needed
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_image_cartoonizer', function (Blueprint $table) {
            // Drop the apiKey column if the migration is rolled back
            $table->dropColumn('apiKey');
        });
    }
}
