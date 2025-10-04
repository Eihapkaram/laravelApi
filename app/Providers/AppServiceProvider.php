<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تأكد إننا في بيئة production أو local فقط
        if (!file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
                Log::info('✅ تم إنشاء رابط storage بنجاح.');
            } catch (\Exception $e) {
                Log::error('❌ فشل إنشاء رابط storage: ' . $e->getMessage());
            }
        }
    }
}
