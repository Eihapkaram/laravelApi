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
        Schema::table('users', function (Blueprint $table) {
            // إضافة الأعمدة مع السماح بالقيم الفارغة
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // جعل تركيبة الإحداثيات فريدة
            $table->unique(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إزالة القيد الفريد أولاً
            $table->dropUnique(['latitude', 'longitude']);

            // حذف الأعمدة
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
