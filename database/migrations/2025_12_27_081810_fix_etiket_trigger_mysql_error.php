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
                DECLARE v_count INT;
                DECLARE v_available_count INT;
                DECLARE v_single_available_count INT;
                DECLARE v_parent_count INT;
                DECLARE v_parent_available_count INT;
                
                -- Get parent_id first to avoid subquery in WHERE clause
                SELECT parent_id INTO v_parent_id 
                FROM products 
                WHERE id = NEW.product_id AND parent_id IS NOT NULL
                LIMIT 1;
                
                -- Calculate counts for the product itself (using temporary table approach)
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_count
                FROM etikets 
                WHERE product_id = NEW.product_id;
                
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_available_count
                FROM etikets 
                WHERE product_id = NEW.product_id AND is_mojood = 1;
                
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_single_available_count
                FROM etikets 
                WHERE product_id = NEW.product_id AND is_mojood = 1;
                
                -- Add child products' etiket counts
                SELECT 
                    COALESCE(SUM(child_counts.total), 0) INTO @child_count
                FROM (
                    SELECT COUNT(*) as total
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id
                ) as child_counts;
                
                SELECT 
                    COALESCE(SUM(child_counts.total), 0) INTO @child_available_count
                FROM (
                    SELECT COUNT(*) as total
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = NEW.product_id AND e2.is_mojood = 1
                ) as child_counts;
                
                -- Update the product itself
                UPDATE products 
                SET count = v_count + COALESCE(@child_count, 0),
                    available_count = v_available_count + COALESCE(@child_available_count, 0),
                    single_available_count = v_single_available_count
                WHERE id = NEW.product_id;
                
                -- Update parent product if this product has a parent
                IF v_parent_id IS NOT NULL THEN
                    -- Calculate counts for parent
                    SELECT 
                        COALESCE(COUNT(*), 0) INTO v_parent_count
                    FROM etikets 
                    WHERE product_id = v_parent_id;
                    
                    SELECT 
                        COALESCE(COUNT(*), 0) INTO v_parent_available_count
                    FROM etikets 
                    WHERE product_id = v_parent_id AND is_mojood = 1;
                    
                    -- Add child products' etiket counts for parent
                    SELECT 
                        COALESCE(SUM(child_counts.total), 0) INTO @parent_child_count
                    FROM (
                        SELECT COUNT(*) as total
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = v_parent_id
                    ) as child_counts;
                    
                    SELECT 
                        COALESCE(SUM(child_counts.total), 0) INTO @parent_child_available_count
                    FROM (
                        SELECT COUNT(*) as total
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = v_parent_id AND e2.is_mojood = 1
                    ) as child_counts;
                    
                    UPDATE products 
                    SET count = v_parent_count + COALESCE(@parent_child_count, 0),
                        available_count = v_parent_available_count + COALESCE(@parent_child_available_count, 0)
                    WHERE id = v_parent_id;
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
                DECLARE v_count INT;
                DECLARE v_available_count INT;
                DECLARE v_single_available_count INT;
                DECLARE v_parent_count INT;
                DECLARE v_parent_available_count INT;
                
                -- Get parent_id first to avoid subquery in WHERE clause
                SELECT parent_id INTO v_parent_id 
                FROM products 
                WHERE id = OLD.product_id AND parent_id IS NOT NULL
                LIMIT 1;
                
                -- Calculate counts for the product itself
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_count
                FROM etikets 
                WHERE product_id = OLD.product_id;
                
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_available_count
                FROM etikets 
                WHERE product_id = OLD.product_id AND is_mojood = 1;
                
                SELECT 
                    COALESCE(COUNT(*), 0) INTO v_single_available_count
                FROM etikets 
                WHERE product_id = OLD.product_id AND is_mojood = 1;
                
                -- Add child products' etiket counts
                SELECT 
                    COALESCE(SUM(child_counts.total), 0) INTO @child_count
                FROM (
                    SELECT COUNT(*) as total
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = OLD.product_id
                ) as child_counts;
                
                SELECT 
                    COALESCE(SUM(child_counts.total), 0) INTO @child_available_count
                FROM (
                    SELECT COUNT(*) as total
                    FROM products child
                    INNER JOIN etikets e2 ON e2.product_id = child.id
                    WHERE child.parent_id = OLD.product_id AND e2.is_mojood = 1
                ) as child_counts;
                
                -- Update the product itself
                UPDATE products 
                SET count = v_count + COALESCE(@child_count, 0),
                    available_count = v_available_count + COALESCE(@child_available_count, 0),
                    single_available_count = v_single_available_count
                WHERE id = OLD.product_id;
                
                -- Update parent product if this product had a parent
                IF v_parent_id IS NOT NULL THEN
                    -- Calculate counts for parent
                    SELECT 
                        COALESCE(COUNT(*), 0) INTO v_parent_count
                    FROM etikets 
                    WHERE product_id = v_parent_id;
                    
                    SELECT 
                        COALESCE(COUNT(*), 0) INTO v_parent_available_count
                    FROM etikets 
                    WHERE product_id = v_parent_id AND is_mojood = 1;
                    
                    -- Add child products' etiket counts for parent
                    SELECT 
                        COALESCE(SUM(child_counts.total), 0) INTO @parent_child_count
                    FROM (
                        SELECT COUNT(*) as total
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = v_parent_id
                    ) as child_counts;
                    
                    SELECT 
                        COALESCE(SUM(child_counts.total), 0) INTO @parent_child_available_count
                    FROM (
                        SELECT COUNT(*) as total
                        FROM products child
                        INNER JOIN etikets e2 ON e2.product_id = child.id
                        WHERE child.parent_id = v_parent_id AND e2.is_mojood = 1
                    ) as child_counts;
                    
                    UPDATE products 
                    SET count = v_parent_count + COALESCE(@parent_child_count, 0),
                        available_count = v_parent_available_count + COALESCE(@parent_child_available_count, 0)
                    WHERE id = v_parent_id;
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
