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
        Schema::table('shopping_cart_items', function (Blueprint $table) {
            $table->foreignId('etiket_id')->nullable()->after('product_id')->constrained('etikets')->onDelete('cascade');
            $table->unique(['user_id', 'etiket_id'], 'user_etiket_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_cart_items', function (Blueprint $table) {
            $table->dropForeign(['etiket_id']);
            $table->dropUnique('user_etiket_unique');
            $table->dropColumn('etiket_id');
        });
    }
};
