<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Assessor\HmlTrialProvider;
use App\Assessor\UnavailableHmlTrialProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HmlTrialProvider::class, UnavailableHmlTrialProvider::class);
    }
    public function boot(): void {}
}
