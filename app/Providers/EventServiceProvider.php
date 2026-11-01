<?php

namespace App\Providers;

use App\Model\FactModule;
use App\Support\Stats;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Event::listen(Registered::class, fn () => Stats::hit('registrations'));
        Event::listen(Verified::class, fn () => Stats::hit('verified'));
        Event::listen(Login::class, fn () => Stats::hit('logins'));
        FactModule::created(fn () => Stats::hit('reports_created'));
    }
}
