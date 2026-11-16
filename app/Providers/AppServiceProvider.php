<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Super Administrator') ? true : null;
        });
    }
}
