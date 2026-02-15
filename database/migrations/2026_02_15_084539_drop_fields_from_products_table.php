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
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['darsad_kharid', 'darsad_vazn_foroosh', 'price', 'weight']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('darsad_kharid')->nullable();
            $table->integer('darsad_vazn_foroosh')->nullable();
            $table->integer('price');
            $table->double('weight');
        });
    }
};
