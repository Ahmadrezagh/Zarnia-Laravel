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
        Schema::create('product_slider_buttons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_slider_id')->constrained('product_sliders')->onDelete('cascade');
            $table->string('title');
            $table->string('query');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_slider_buttons');
    }
};
