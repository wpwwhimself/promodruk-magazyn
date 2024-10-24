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
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->boolean("marking_import_enabled")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->dropColumn("marking_import_enabled");
        });
    }
};