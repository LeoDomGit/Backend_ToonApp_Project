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
        // Drop the column if it exists
        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'edit_image_path')) {
                $table->dropColumn('edit_image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
