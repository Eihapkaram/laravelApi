<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Load Passport keys from environment variables if they exist
        $privateKey = env('PASSPORT_PRIVATE_KEY');
        $publicKey  = env('PASSPORT_PUBLIC_KEY');

        if ($privateKey && $publicKey) {
            Passport::loadKeysFrom([
                'private' => $privateKey,
                'public'  => $publicKey,
            ]);
        }

        // Optional: disable automatic route registration
        // Passport::ignoreRoutes();
    }
}
