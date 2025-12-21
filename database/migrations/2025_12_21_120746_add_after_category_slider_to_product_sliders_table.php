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
            $table->integer('after_category_slider')->default(0)->after('before_category_slider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sliders', function (Blueprint $table) {
            $table->dropColumn('after_category_slider');
        });
    }
};
