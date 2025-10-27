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
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wallet_number')->nullable()->after('email');
            $table->string('front_id_image')->nullable()->after('wallet_number');
            $table->string('back_id_image')->nullable()->after('front_id_image');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wallet_number', 'front_id_image', 'back_id_image']);
        });
    }
};
