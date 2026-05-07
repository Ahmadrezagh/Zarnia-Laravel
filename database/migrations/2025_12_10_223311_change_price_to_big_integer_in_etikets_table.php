<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal package
        \DB::statement('ALTER TABLE `etikets` MODIFY `price` BIGINT NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal package
        \DB::statement('ALTER TABLE `etikets` MODIFY `price` INT NOT NULL');
    }
};
