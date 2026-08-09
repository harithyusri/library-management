<?php

namespace App\Providers;

use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\RoomBooking;
use App\Policies\FinePolicy;
use App\Policies\LoanPolicy;
use App\Policies\MaintenanceReportPolicy;
use App\Policies\RoomBookingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Listeners\AuditAuthListener;
use Inertia\Inertia;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Loan::class            => LoanPolicy::class,
        RoomBooking::class     => RoomBookingPolicy::class,
        MaintenanceReport::class => MaintenanceReportPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        // Fine policy is on Loan model (fines are loan attributes)
        \Illuminate\Support\Facades\Gate::policy(Loan::class, FinePolicy::class);

        if (config('app.env') !== 'local' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Event::listen([
            Login::class,
            Logout::class,
            Failed::class,
        ], AuditAuthListener::class);

        Inertia::share('unread_notifications_count', function () {
            return auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
        });
    }
}
