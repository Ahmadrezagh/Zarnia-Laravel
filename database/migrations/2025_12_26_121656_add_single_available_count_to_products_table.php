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
        // Add single_available_count column to products table if it doesn't exist
        if (!Schema::hasColumn('products', 'single_available_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('single_available_count')->default(0)->after('available_count');
            });
        }

        // Initialize single_available_count for existing products (only direct etikets, no children)
        DB::statement("
            UPDATE products p
            INNER JOIN (
                SELECT 
                    p2.id,
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM etikets e 
                        WHERE e.product_id = p2.id 
                        AND e.is_mojood = 1
                    ), 0) as single_available_count
                FROM products p2
            ) as counts ON counts.id = p.id
            SET p.single_available_count = counts.single_available_count
        ");

        // Update existing triggers to also update single_available_count
        // Drop and recreate triggers to include single_available_count

        // Trigger for INSERT
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_insert
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_insert
            AFTER INSERT ON etikets
            FOR EACH ROW
            BEGIN
                -- Update the product itself
                UPDATE products 
                SET count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id
                ), 0),
                available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id
                    AND e2.is_mojood = 1
                ), 0),
                single_available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                ), 0)
                WHERE id = NEW.product_id;
                
                -- Update parent product if this product has a parent
                UPDATE products parent
                SET parent.count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                ), 0),
                parent.available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                    AND e2.is_mojood = 1
                ), 0)
                WHERE parent.id = (SELECT parent_id FROM products WHERE id = NEW.product_id AND parent_id IS NOT NULL);
            END
        ");

        // Trigger for UPDATE
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_update
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_update
            AFTER UPDATE ON etikets
            FOR EACH ROW
            BEGIN
                -- Update the product itself
                UPDATE products 
                SET count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id
                ), 0),
                available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id
                    AND e2.is_mojood = 1
                ), 0),
                single_available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id 
                    AND is_mojood = 1
                ), 0)
                WHERE id = NEW.product_id;
                
                -- Update parent product if this product has a parent
                UPDATE products parent
                SET parent.count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                ), 0),
                parent.available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                    AND e2.is_mojood = 1
                ), 0)
                WHERE parent.id = (SELECT parent_id FROM products WHERE id = NEW.product_id AND parent_id IS NOT NULL);
            END
        ");

        // Trigger for DELETE
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_delete
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_delete
            AFTER DELETE ON etikets
            FOR EACH ROW
            BEGIN
                -- Update the product itself
                UPDATE products 
                SET count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = OLD.product_id
                ), 0),
                available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = OLD.product_id
                    AND e2.is_mojood = 1
                ), 0),
                single_available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id 
                    AND is_mojood = 1
                ), 0)
                WHERE id = OLD.product_id;
                
                -- Update parent product if this product had a parent
                UPDATE products parent
                SET parent.count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                ), 0),
                parent.available_count = COALESCE((
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = parent.id 
                    AND is_mojood = 1
                ), 0) + COALESCE((
                    SELECT COUNT(*) 
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = parent.id
                    AND e2.is_mojood = 1
                ), 0)
                WHERE parent.id = (SELECT parent_id FROM products WHERE id = OLD.product_id AND parent_id IS NOT NULL);
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'single_available_count')) {
                $table->dropColumn('single_available_count');
            }
        });
    }
};
