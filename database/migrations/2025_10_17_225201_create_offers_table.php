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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // العنوان والوصف
            $table->string('title');
            $table->text('description')->nullable();

            // بانر العرض (مسار الصورة)
            $table->string('banner')->nullable();

            // المنتج المرتبط بالعرض (اختياري)
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // السعر المخفض أو نسبة الخصم
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');

            // تاريخ العرض
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // حالة العرض
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
