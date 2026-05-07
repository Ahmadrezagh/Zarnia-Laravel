<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['gold', 'none_gold', 'comprehensive_product'])
                  ->default('gold')
                  ->after('id');
        });

        // Back-fill existing rows: comprehensive products → comprehensive_product
        DB::table('products')->where('is_comprehensive', 1)->update(['type' => 'comprehensive_product']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
