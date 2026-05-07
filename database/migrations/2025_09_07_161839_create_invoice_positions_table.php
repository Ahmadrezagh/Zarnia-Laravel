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
        Schema::create('invoice_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('invoice_templates');
            $table->string('key'); // نام ثابت/متغیر، مثلا 'company_logo' یا 'total_label'
            $table->string('type'); // 'fixed' برای ثابت، 'variable' برای دینامیک (بعدا)
            $table->text('value')->nullable(); // مقدار ثابت، مثلا "جمع کل:"
            $table->float('x'); // موقعیت x در px یا mm
            $table->float('y'); // موقعیت y
            $table->string('font_family')->default('Vazirmatn'); // برای پارسی
            $table->integer('font_size')->default(12);
            $table->string('color')->default('#000000');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_positions');
    }
};
