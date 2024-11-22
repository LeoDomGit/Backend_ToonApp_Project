<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id(); // Auto increment ID
            $table->uuid('trans_id')->unique(); // Unique trans_id (UUID)
            $table->string('status'); // Status of the transaction
            $table->string('uid'); // User identifier
            $table->string('api_token'); // API token
            $table->string('device_id'); // Device identifier
            $table->string('platform'); // Platform (e.g., iOS, Android)
            $table->json('jconfig'); // Store the decoded jconfig as a JSON field
            $table->timestamps(); // Created at and Updated at
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_logs');
    }
}
