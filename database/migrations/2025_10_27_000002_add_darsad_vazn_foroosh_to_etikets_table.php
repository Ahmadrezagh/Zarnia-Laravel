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
            $table->integer('darsad_vazn_foroosh')->nullable()->after('mazaneh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etikets', function (Blueprint $table) {
            $table->dropColumn('darsad_vazn_foroosh');
        });
    }
};

