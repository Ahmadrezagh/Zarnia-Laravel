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

        // Initialize count for existing products (including children's etikets)
        // Using a derived table to avoid MySQL error 1093
        DB::statement("
            UPDATE products p
            INNER JOIN (
                SELECT 
                    p2.id,
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM etikets e 
                        WHERE e.product_id = p2.id
                    ), 0) + COALESCE((
                        SELECT COUNT(*) 
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = p2.id
                    ), 0) as total_count
                FROM products p2
            ) as counts ON counts.id = p.id
            SET p.count = counts.total_count
        ");

        // Initialize available_count for existing products (including children's etikets)
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
                    ), 0) + COALESCE((
                        SELECT COUNT(*) 
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = p2.id
                        AND e2.is_mojood = 1
                    ), 0) as total_available_count
                FROM products p2
            ) as available_counts ON available_counts.id = p.id
            SET p.available_count = available_counts.total_available_count
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

        // Create trigger to update count and available_count when etikets are updated
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

        // Create trigger to update count and available_count when etikets are deleted
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
                ), 0)
                WHERE id = OLD.product_id;
                
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
                WHERE parent.id = (SELECT parent_id FROM products WHERE id = OLD.product_id AND parent_id IS NOT NULL);
            END
        ");

        // Create trigger to update parent's count when product's parent_id changes
        DB::statement("
            DROP TRIGGER IF EXISTS update_parent_count_after_product_update
        ");
        DB::statement("
            CREATE TRIGGER update_parent_count_after_product_update
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                -- If parent_id changed, update old and new parents
                IF OLD.parent_id != NEW.parent_id OR (OLD.parent_id IS NULL AND NEW.parent_id IS NOT NULL) OR (OLD.parent_id IS NOT NULL AND NEW.parent_id IS NULL) THEN
                    -- Update old parent if exists
                    IF OLD.parent_id IS NOT NULL THEN
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
                        WHERE parent.id = OLD.parent_id;
                    END IF;
                    
                    -- Update new parent if exists
                    IF NEW.parent_id IS NOT NULL THEN
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
                        WHERE parent.id = NEW.parent_id;
                    END IF;
                END IF;
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
        DB::statement('DROP TRIGGER IF EXISTS update_parent_count_after_product_update');

        // Drop columns
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['count', 'available_count']);
        });
    }
};
