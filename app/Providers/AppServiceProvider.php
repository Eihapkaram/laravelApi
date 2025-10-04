<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // إنشاء symlink بين storage/public و public/storage لو مش موجود
        if (!file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // ممكن تكتب لوج هنا لو حابب
                \Log::error('فشل إنشاء رابط storage: ' . $e->getMessage());
            }
        }
    }
}
