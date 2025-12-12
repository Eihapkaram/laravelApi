<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('supplier_product', function (Blueprint $table) {
        $table->id();
        $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
         // 🔹 سعر التوريد هنا
        $table->decimal('supplier_price', 10, 2);

        // 🔹 كمية الحد الأدنى (اختياري)
        $table->integer('min_quantity')->default(1);

        // 🔹 الحالة (نشط / موقوف)
        $table->boolean('active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_product');
    }
};
