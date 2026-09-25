<?php

namespace App\Providers;

use App\Services\Ai\AiProvider;
use App\Services\Ai\MockAiProvider;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            return match (config('markethub.payment_gateway')) {
                default => new MockPaymentGateway,
            };
        });
        $this->app->singleton(AiProvider::class, MockAiProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
