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
        Schema::table('product_sliders', function (Blueprint $table) {
            $table->integer('before_category_slider')->default(0)->after('show_more');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sliders', function (Blueprint $table) {
            $table->dropColumn('before_category_slider');
        });
    }
};
