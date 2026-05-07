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
        Schema::table('orders', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['address_id']);
            $table->dropForeign(['shipping_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Modify columns to be nullable
            $table->unsignedBigInteger('address_id')->nullable()->change();
            $table->unsignedBigInteger('shipping_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Re-add foreign keys with nullable columns
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->foreign('shipping_id')->references('id')->on('shippings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['address_id']);
            $table->dropForeign(['shipping_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Make columns NOT nullable again
            $table->unsignedBigInteger('address_id')->nullable(false)->change();
            $table->unsignedBigInteger('shipping_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Re-add foreign keys
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->foreign('shipping_id')->references('id')->on('shippings')->onDelete('cascade');
        });
    }
};
