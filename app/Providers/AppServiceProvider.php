<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use App\Models\Customer;
use App\Models\Payment;
use App\Policies\IssueVoucherPolicy;
use App\Policies\ReturnVoucherPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PaymentPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        IssueVoucher::class => IssueVoucherPolicy::class,
        ReturnVoucher::class => ReturnVoucherPolicy::class,
        Customer::class => CustomerPolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

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
        // Bypass all policies in local environment for development
        if (app()->environment('local')) {
            Gate::before(function ($user, $ability) {
                // Allow super-admin to bypass all policies
                if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                    return true;
                }
                
                // For development: allow all authenticated users to bypass policies
                return true;  // ✅ Bypass all policies for testing
            });
        }
        
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
