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
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('terms_and_conditions')->nullable()->after('hotphone');
            $table->longText('shipping_and_return_policy')->nullable()->after('terms_and_conditions');
            $table->longText('privacy_policy')->nullable()->after('shipping_and_return_policy');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'terms_and_conditions',
                'shipping_and_return_policy',
                'privacy_policy',
            ]);
        });
    }
};
