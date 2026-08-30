<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        // The app is Bootstrap-only (no Tailwind) — Paginator's own
        // default view is Tailwind-styled and would render unstyled.
        Paginator::useBootstrapFive();

        // A mass-assigned field missing from $fillable fails LOUDLY
        // outside production instead of the update() silently no-oping —
        // caught a real disable/enable bug during Phase 4 development
        // that would otherwise have shipped silently broken.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Superadmin bypasses every permission check — the standard
        // spatie/laravel-permission pattern for a super-user role. This is
        // not the "is_admin = true" shortcut blueprint §29 warns against:
        // it's one properly-scoped, MFA-mandatory, protected role (§30)
        // inside the real RBAC system, not a substitute for it. Explicitly
        // assigning every permission to Superadmin instead would silently
        // stop covering permissions added by later phases.
        Gate::before(fn ($user, string $ability) => $user->isSuperadmin() ? true : null);

        // Explicit rather than relying on naming-convention auto-discovery,
        // which isn't reliable for App\Models\Role (config/permission.php
        // swaps in our subclass — see its data_scope cast — but the class
        // itself still lives one namespace segment off the App\Models\X
        // -> App\Policies\XPolicy convention the guesser expects).
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
