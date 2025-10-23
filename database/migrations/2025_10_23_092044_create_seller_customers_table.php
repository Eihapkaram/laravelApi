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
        Schema::create('seller_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade'); // البائع
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade'); // العميل
            $table->timestamps();

            $table->unique(['seller_id', 'customer_id']); // علشان ميكررش نفس العميل للبائع
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_customers');
    }
};
