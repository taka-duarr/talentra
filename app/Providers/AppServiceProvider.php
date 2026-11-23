<?php

namespace App\Providers;

use App\Payments\ManualPaymentGateway;
use App\Payments\PaymentGateway;
use App\Services\Analysis\AnalysisDriver;
use App\Services\Analysis\AnthropicAnalysisDriver;
use App\Services\Analysis\RuleBasedAnalysisDriver;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => match (config('talentra.payments.gateway')) {
            'manual' => new ManualPaymentGateway,
            default => throw new InvalidArgumentException('Payment gateway belum didukung: '.config('talentra.payments.gateway')),
        });

        $this->app->bind(AnalysisDriver::class, function () {
            $useAnthropic = config('talentra.analysis.driver') === 'anthropic'
                && filled(config('talentra.analysis.anthropic.api_key'));

            return $useAnthropic
                ? $this->app->make(AnthropicAnalysisDriver::class)
                : $this->app->make(RuleBasedAnalysisDriver::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
