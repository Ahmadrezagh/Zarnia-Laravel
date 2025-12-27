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
        // Drop the trigger that causes MySQL error 1442
        // The trigger tries to update products while products is already being updated
        // This causes a conflict. Parent count updates can be handled in application code if needed.
        DB::statement("DROP TRIGGER IF EXISTS update_parent_count_after_product_update");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the trigger (with potential error 1442) if needed
        // For now, we'll leave it dropped as it causes conflicts
        DB::statement("
            CREATE TRIGGER update_parent_count_after_product_update
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                -- If parent_id changed, update old and new parents
                IF OLD.parent_id != NEW.parent_id OR (OLD.parent_id IS NULL AND NEW.parent_id IS NOT NULL) OR (OLD.parent_id IS NOT NULL AND NEW.parent_id IS NULL) THEN
                    -- Update old parent if exists (only direct etiket counts)
                    IF OLD.parent_id IS NOT NULL THEN
                        UPDATE products 
                        SET count = (
                            SELECT COUNT(*) 
                            FROM etikets 
                            WHERE product_id = OLD.parent_id
                        ),
                        available_count = (
                            SELECT COUNT(*) 
                            FROM etikets 
                            WHERE product_id = OLD.parent_id AND is_mojood = 1
                        )
                        WHERE id = OLD.parent_id;
                    END IF;
                    
                    -- Update new parent if exists (only direct etiket counts)
                    IF NEW.parent_id IS NOT NULL THEN
                        UPDATE products 
                        SET count = (
                            SELECT COUNT(*) 
                            FROM etikets 
                            WHERE product_id = NEW.parent_id
                        ),
                        available_count = (
                            SELECT COUNT(*) 
                            FROM etikets 
                            WHERE product_id = NEW.parent_id AND is_mojood = 1
                        )
                        WHERE id = NEW.parent_id;
                    END IF;
                END IF;
            END
        ");
    }
};

