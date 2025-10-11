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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index(); // IPv4/IPv6, anonymize if needed
            $table->string('title')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->index();
            $table->string('referrer')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Index for queries
            $table->index(['created_at', 'url']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
