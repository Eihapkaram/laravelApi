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
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('status', [
                'draft',
                'sent',
                'preparing',
                'ready',
                'received',
                'cancelled'
            ])->default('sent');
            $table->timestamp('responded_at')->nullable();
            $table->text('supplier_reject_reason')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('supplier_orders');
    }
};
