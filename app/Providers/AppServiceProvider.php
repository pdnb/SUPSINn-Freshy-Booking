<?php

namespace App\Providers;

use App\Contracts\SlipVerifier;
use App\Services\Payment\StubSlipVerifier;
use App\Support\ThaiDate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interface → implementation: https://laravel.com/docs/13.x/container#binding-interfaces-to-implementations
        $this->app->bind(SlipVerifier::class, StubSlipVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::macro('toThaiDatetime', function (): string {
            /** @var Carbon $this */
            return ThaiDate::datetime($this);
        });

        // Named limiter: https://laravel.com/docs/13.x/routing#defining-rate-limiters
        RateLimiter::for('order-tracking', function (Request $request) {
            return app()->runningUnitTests()
                ? Limit::none()
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('line-session', function (Request $request) {
            return app()->runningUnitTests()
                ? Limit::none()
                : Limit::perMinute(30)->by($request->ip());
        });
    }
}
