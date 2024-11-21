<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransIdToAiImageCartoonizer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_image_cartoonizer', function (Blueprint $table) {
            // Add the trans_id column to the table
            $table->string('trans_id')->nullable()->after('apiKey'); // Adjust the position as needed
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
            // Drop the trans_id column if the migration is rolled back
            $table->dropColumn('trans_id');
        });
    }
}
