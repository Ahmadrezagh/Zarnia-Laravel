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
        Schema::table('etikets', function (Blueprint $table) {
            $table->enum('type', ['real', 'comprehensive'])
                ->default('real')
                ->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etikets', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

