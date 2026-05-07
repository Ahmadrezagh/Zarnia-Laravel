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
        Schema::create('gift_structures', function (Blueprint $table) {
            $table->id();
            $table->integer('from_price')->comment('Minimum order price to qualify');
            $table->integer('to_price')->comment('Maximum order price to qualify');
            $table->integer('amount')->nullable()->comment('Fixed discount amount in Toman');
            $table->integer('percentage')->nullable()->comment('Discount percentage (0-100)');
            $table->integer('limit_in_days')->comment('Number of days the generated code is valid');
            $table->boolean('is_active')->default(true)->comment('Is this gift structure active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_structures');
    }
};
