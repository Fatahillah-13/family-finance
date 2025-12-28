<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Registered;
use App\Listeners\CreateDefaultHouseholdForNewUser;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        Registered::class => [
            CreateDefaultHouseholdForNewUser::class,
        ],
    ];

    /**
     * Register services.
     */
    // public function register(): void
    // {
    //     //
    // }

    /**
 * Bootstrap services.
 */
    // public function boot(): void
    // {
    //     //
    // }
}
