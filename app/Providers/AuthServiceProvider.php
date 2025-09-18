<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Use keys from environment variables
        if (env('PASSPORT_PRIVATE_KEY') && env('PASSPORT_PUBLIC_KEY')) {
            Passport::loadKeysFrom([
                'private' => env('PASSPORT_PRIVATE_KEY'),
                'public'  => env('PASSPORT_PUBLIC_KEY'),
            ]);
        }

        // Optional: disable routes registration if not needed
        // Passport::ignoreRoutes();
    }
}
