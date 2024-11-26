<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->string('gmail')->nullable()->after('key'); // Thêm cột gmail, cho phép null
        });
    }

    public function down(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->dropColumn('gmail'); // Xóa cột gmail nếu rollback
        });
    }
};
