<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add orderable_after_out_of_stock to etikets table (only if it doesn't exist)
        if (!Schema::hasColumn('etikets', 'orderable_after_out_of_stock')) {
            Schema::table('etikets', function (Blueprint $table) {
                $table->boolean('orderable_after_out_of_stock')->default(false)->after('darsad_vazn_foroosh');
            });
        }
        
        // Check if products table still has orderable_after_out_of_stock column
        if (Schema::hasColumn('products', 'orderable_after_out_of_stock')) {
            // Use a temporary table to store product_id -> orderable_after_out_of_stock mapping
            // This avoids trigger conflicts when updating etikets
            DB::statement('
                CREATE TEMPORARY TABLE temp_product_orderable AS
                SELECT id, COALESCE(orderable_after_out_of_stock, 0) as orderable_after_out_of_stock
                FROM products
            ');
            
            // Update etikets using the temporary table (no direct reference to products)
            DB::statement('
                UPDATE etikets e
                INNER JOIN temp_product_orderable t ON t.id = e.product_id
                SET e.orderable_after_out_of_stock = t.orderable_after_out_of_stock
            ');
            
            // Drop temporary table
            DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_product_orderable');
            
            // Remove orderable_after_out_of_stock from products table
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('orderable_after_out_of_stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add orderable_after_out_of_stock back to products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('orderable_after_out_of_stock')->default(false)->after('visits');
        });
        
        // Copy data back from etikets to products (use the most common value per product)
        DB::statement('
            UPDATE products p
            SET p.orderable_after_out_of_stock = (
                SELECT COALESCE(MAX(e.orderable_after_out_of_stock), 0)
                FROM etikets e
                WHERE e.product_id = p.id
                LIMIT 1
            )
        ');
        
        // Remove orderable_after_out_of_stock from etikets table
        Schema::table('etikets', function (Blueprint $table) {
            $table->dropColumn('orderable_after_out_of_stock');
        });
    }
};
