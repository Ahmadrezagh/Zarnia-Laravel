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
        // Add count column to products table if it doesn't exist
        if (!Schema::hasColumn('products', 'count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('count')->default(0)->after('visits');
            });
        }

        // Add available_count column to products table if it doesn't exist
        if (!Schema::hasColumn('products', 'available_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('available_count')->default(0)->after('count');
            });
        }

        // Initialize count for existing products
        DB::statement("
            UPDATE products p
            SET p.count = (
                SELECT COUNT(*) 
                FROM etikets e 
                WHERE e.product_id = p.id
            )
        ");

        // Initialize available_count for existing products
        DB::statement("
            UPDATE products p
            SET p.available_count = (
                SELECT COUNT(*) 
                FROM etikets e 
                WHERE e.product_id = p.id 
                AND e.is_mojood = 1
            )
        ");

        // Create trigger to update count and available_count when etikets are inserted
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_insert
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_insert
            AFTER INSERT ON etikets
            FOR EACH ROW
            BEGIN
                UPDATE products 
                SET count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id
                ),
                available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                )
                WHERE id = NEW.product_id;
            END
        ");

        // Create trigger to update count and available_count when etikets are updated
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_update
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_update
            AFTER UPDATE ON etikets
            FOR EACH ROW
            BEGIN
                UPDATE products 
                SET count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id
                ),
                available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                )
                WHERE id = NEW.product_id;
            END
        ");

        // Create trigger to update count and available_count when etikets are deleted
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_delete
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_delete
            AFTER DELETE ON etikets
            FOR EACH ROW
            BEGIN
                UPDATE products 
                SET count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id
                ),
                available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id 
                    AND is_mojood = 1
                )
                WHERE id = OLD.product_id;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS update_product_count_after_insert');
        DB::statement('DROP TRIGGER IF EXISTS update_product_count_after_update');
        DB::statement('DROP TRIGGER IF EXISTS update_product_count_after_delete');

        // Drop columns
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['count', 'available_count']);
        });
    }
};
