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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('titel', 200);
            $table->longText('description');
            $table->smallInteger('votes');
            $table->string('inCount');
            $table->string('Counttype')->nullable();
            $table->string('inCounttype')->nullable();
            $table->string('discount')->nullable();
            $table->string('url');
            $table->string('brand');
            $table->string('img')->nullable();
            $table->json('images_url')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreignId('page_id')->references('id')->on('pages')->cascadeOnDelete();
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
        Schema::dropIfExists('products');
    }
};
