<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('administrar', fn (User $user): bool => $user->isAdmin());
        Gate::define('actualizar-estado', fn (User $user): bool => in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TECNICO], true));
    }
}
