<?php

namespace App\Providers;

use App\Payments\ManualPaymentGateway;
use App\Payments\MidtransPaymentGateway;
use App\Payments\PaymentGateway;
use App\Services\Analysis\AnalysisDriver;
use App\Services\Analysis\AnthropicAnalysisDriver;
use App\Services\Analysis\NvidiaAnalysisDriver;
use App\Services\Analysis\RuleBasedAnalysisDriver;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => match (config('talentra.payments.gateway')) {
            'manual' => new ManualPaymentGateway,
            'midtrans' => $this->app->make(MidtransPaymentGateway::class),
            default => throw new InvalidArgumentException('Payment gateway belum didukung: '.config('talentra.payments.gateway')),
        });

        $this->app->bind(AnalysisDriver::class, fn () => match (true) {
            config('talentra.analysis.driver') === 'nvidia' && filled(config('talentra.analysis.nvidia.api_key')) => $this->app->make(NvidiaAnalysisDriver::class),
            config('talentra.analysis.driver') === 'anthropic' && filled(config('talentra.analysis.anthropic.api_key')) => $this->app->make(AnthropicAnalysisDriver::class),
            default => $this->app->make(RuleBasedAnalysisDriver::class),
        });
    }

    public function boot(): void
    {
        // Dates are shown in Indonesian; validation messages keep APP_LOCALE until translations exist.
        Carbon::setLocale('id');
    }
}
