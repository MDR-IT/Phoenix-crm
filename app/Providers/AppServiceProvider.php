<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\FollowUpPolicy;
use App\Policies\LeadPolicy;
use App\Policies\NotePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Services\Messaging\MessageProviderInterface;
use App\Services\Messaging\NullEmailProvider;
use App\Services\Messaging\NullSmsProvider;
use App\Services\Messaging\OutboundMessenger;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NullEmailProvider::class);
        $this->app->singleton(NullSmsProvider::class);

        $this->app->when(OutboundMessenger::class)
            ->needs('$emailProvider')
            ->give(NullEmailProvider::class);

        $this->app->when(OutboundMessenger::class)
            ->needs('$smsProvider')
            ->give(NullSmsProvider::class);

        $this->app->bind(MessageProviderInterface::class, NullEmailProvider::class);

        $this->app->singleton(OutboundMessenger::class, function ($app) {
            return new OutboundMessenger(
                $app->make(NullEmailProvider::class),
                $app->make(NullSmsProvider::class),
            );
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(FollowUp::class, FollowUpPolicy::class);

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Super Administrator') ? true : null;
        });
    }
}
