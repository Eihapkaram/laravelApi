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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('store_name')->nullable();
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->text('shipping_address')->nullable();
            $table->enum('payment_method', ['cod', 'credit_card', 'paypal'])->default('cod');
             $table->string('store_banner')->nullable();
             // ✅ حالة موافقة العميل
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('city')->nullable();
            $table->string('governorate')->nullable();
            $table->string('street')->nullable();
            $table->string('phone')->nullable();
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
        Schema::dropIfExists('orders');
    }
};
