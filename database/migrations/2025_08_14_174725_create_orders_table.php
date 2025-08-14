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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('address_id')->constrained('addresses')->onDelete('cascade');
            $table->foreignId('shipping_id')->constrained('shippings')->onDelete('cascade');
            $table->foreignId('shipping_time_id')->nullable()->constrained('shipping_times')->onDelete('cascade');
            $table->foreignId('gateway_id')->nullable()->constrained('gateways')->onDelete('cascade');
            $table->string('status');
            $table->string('discount_code');
            $table->integer('discount_price')->nullable();
            $table->integer('discount_percentage')->nullable();
            $table->integer('total_amount');
            $table->integer('final_amount');
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
