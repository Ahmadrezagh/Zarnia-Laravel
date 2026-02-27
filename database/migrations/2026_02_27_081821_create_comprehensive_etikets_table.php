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
        Schema::create('comprehensive_etikets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etiket_id')
                ->constrained('etikets')
                ->onDelete('cascade');
            $table->foreignId('related_etiket_id')
                ->constrained('etikets')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprehensive_etikets');
    }
};

