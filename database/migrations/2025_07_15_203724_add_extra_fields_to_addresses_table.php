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
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('postal_code');
            $table->unsignedInteger('province_id');
            $table->unsignedInteger('city_id');

            $table->foreign('province_id')->references('id')->on('iran_provinces')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('iran_cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign('addresses_province_id_foreign');
            $table->dropForeign('addresses_city_id_foreign');
            $table->dropColumn('postal_code');
        });
    }
};
