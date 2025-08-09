<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            // First drop the foreign key
            $table->dropForeign(['attribute_group_id']);

            // Then drop the column
            $table->dropColumn('attribute_group_id');

            // Add new columns
            $table->string('prefix_sentence')->nullable()->after('name');
            $table->string('postfix_sentence')->nullable()->after('prefix_sentence');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            // Re-add column
            $table->unsignedBigInteger('attribute_group_id')->nullable();

            // Recreate foreign key (update 'attribute_groups' if your FK table name is different)
            $table->foreign('attribute_group_id')
                ->references('id')
                ->on('attribute_groups')
                ->onDelete('cascade');

            // Drop new columns
            $table->dropColumn(['prefix_sentence', 'postfix_sentence']);
        });
    }
};
