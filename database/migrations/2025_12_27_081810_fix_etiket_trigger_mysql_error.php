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
        // Use a simpler approach: only update direct etiket counts, skip child product counts in trigger
        // Child product counts can be recalculated separately if needed
        DB::statement("
            DROP TRIGGER IF EXISTS update_product_count_after_update
        ");
        DB::statement("
            CREATE TRIGGER update_product_count_after_update
            AFTER UPDATE ON etikets
            FOR EACH ROW
            BEGIN
                DECLARE v_parent_id INT DEFAULT 0;
                
                -- Get parent_id first
                SELECT COALESCE(parent_id, 0) INTO v_parent_id 
                FROM products 
                WHERE id = NEW.product_id
                LIMIT 1;
                
                -- Update the product itself - only direct etiket counts (no child products)
                UPDATE products 
                SET count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id
                ),
                available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id AND is_mojood = 1
                ),
                single_available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = NEW.product_id AND is_mojood = 1
                )
                WHERE id = NEW.product_id;
                
                -- Update parent product if this product has a parent
                -- Only update direct etiket counts for parent (no child products)
                IF v_parent_id > 0 THEN
                    UPDATE products 
                    SET count = (
                        SELECT COUNT(*) 
                        FROM etikets 
                        WHERE product_id = v_parent_id
                    ),
                    available_count = (
                        SELECT COUNT(*) 
                        FROM etikets 
                        WHERE product_id = v_parent_id AND is_mojood = 1
                    )
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
                DECLARE v_parent_id INT DEFAULT 0;
                
                -- Get parent_id first
                SELECT COALESCE(parent_id, 0) INTO v_parent_id 
                FROM products 
                WHERE id = OLD.product_id
                LIMIT 1;
                
                -- Update the product itself - only direct etiket counts (no child products)
                UPDATE products 
                SET count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id
                ),
                available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id AND is_mojood = 1
                ),
                single_available_count = (
                    SELECT COUNT(*) 
                    FROM etikets 
                    WHERE product_id = OLD.product_id AND is_mojood = 1
                )
                WHERE id = OLD.product_id;
                
                -- Update parent product if this product had a parent
                -- Only update direct etiket counts for parent (no child products)
                IF v_parent_id > 0 THEN
                    UPDATE products 
                    SET count = (
                        SELECT COUNT(*) 
                        FROM etikets 
                        WHERE product_id = v_parent_id
                    ),
                    available_count = (
                        SELECT COUNT(*) 
                        FROM etikets 
                        WHERE product_id = v_parent_id AND is_mojood = 1
                    )
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
