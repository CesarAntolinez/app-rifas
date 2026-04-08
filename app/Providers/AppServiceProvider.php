<?php

namespace App\Providers;

use App\Listeners\UpdateLastLogin;
use App\Models\Participant;
use App\Models\Raffle;
use App\Models\User;
use App\Policies\ParticipantPolicy;
use App\Policies\RafflePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Raffle::class, RafflePolicy::class);
        Gate::policy(Participant::class, ParticipantPolicy::class);

        Event::listen(Login::class, UpdateLastLogin::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)
                ->by($request->input('email') . '|' . $request->ip());
        });
    }
}
