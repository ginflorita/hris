<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Length + composition rather than Password::uncompromised(): an
        // HRIS is commonly deployed on-prem/behind a locked-down corporate
        // proxy, and making the password rule itself depend on reaching
        // api.pwnedpasswords.com isn't a dependency worth taking here.
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols());
    }
}
