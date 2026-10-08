<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Installer;
use App\Support\RuntimeConfig;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
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
        Relation::morphMap([
            'user' => User::class,
            'customer' => Customer::class,
            'subscription' => Subscription::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'product' => Product::class,
            'supplier' => Supplier::class,
            'seller' => Seller::class,
        ]);

        Paginator::useTailwind();

        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('staff', fn (User $user) => $user->isStaff());

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                ActivityLog::record('login', $event->user, "Login {$event->user->email}");
            }
        });

        // A fresh or restored database must never be read through stale cached settings.
        Event::listen([MigrationsStarted::class, DatabaseRefreshed::class], function () {
            Setting::flushCache();
            Cache::forget('default_currency');
        });

        if (Installer::isInstalled()) {
            RuntimeConfig::apply();
        }
    }
}
