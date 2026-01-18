<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
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


       // Register Passport routes for OAuth2 authorization
       Passport::routes();

       // Set expiration for regular OAuth access tokens to 1 day
       Passport::tokensExpireIn(Carbon::now()->addDays(1));



       // If you're using the "personal access tokens" (e.g., API keys), you can do this:
       Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(1));


    }
}
