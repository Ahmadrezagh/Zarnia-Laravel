<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('invoice_snapshot')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->text('invoice_product_image')->nullable();
            $table->string('invoice_weight')->nullable();
            $table->string('invoice_ayar', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('invoice_snapshot');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['invoice_product_image', 'invoice_weight', 'invoice_ayar']);
        });
    }
};
