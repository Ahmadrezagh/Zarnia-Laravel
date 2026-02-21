<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->foreignId('etiket_id')->nullable()->after('attribute_id')->constrained('etikets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['etiket_id']);
            $table->dropColumn('etiket_id');
            $table->foreignId('product_id')->nullable()->after('attribute_id')->constrained('products')->onDelete('cascade');
        });
    }
};
