<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the trigger to avoid MySQL error 1093
        // The issue is using a subquery that selects from products while updating products
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_update
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_update
            AFTER UPDATE ON etikets
            FOR EACH ROW
            BEGIN
                DECLARE v_parent_id INT;
                
                -- Get parent_id first to avoid subquery in WHERE clause
                SELECT parent_id INTO v_parent_id 
                FROM products 
                WHERE id = NEW.product_id AND parent_id IS NOT NULL
                LIMIT 1;
                
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
                IF v_parent_id IS NOT NULL THEN
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
                    WHERE parent.id = v_parent_id;
                END IF;
            END
        ");
        
        // Also fix the DELETE trigger
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_delete
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_delete
            AFTER DELETE ON etikets
            FOR EACH ROW
            BEGIN
                DECLARE v_parent_id INT;
                
                -- Get parent_id first to avoid subquery in WHERE clause
                SELECT parent_id INTO v_parent_id 
                FROM products 
                WHERE id = OLD.product_id AND parent_id IS NOT NULL
                LIMIT 1;
                
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
                IF v_parent_id IS NOT NULL THEN
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
                    WHERE parent.id = v_parent_id;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the old triggers (with the bug) if needed
        // For now, just drop them
        DB::statement("DROP TRIGGER IF EXISTS update_product_count_after_update");
        DB::statement("DROP TRIGGER IF EXISTS update_product_count_after_delete");
    }
};
