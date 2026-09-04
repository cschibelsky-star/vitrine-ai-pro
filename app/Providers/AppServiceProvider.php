<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');

            if (! app()->runningInConsole() && app()->environment('production')) {
                $configuredUrl = (string) config('app.url');
                $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
                $requestHost = request()->getHost();

                if (is_string($configuredHost) && $configuredHost !== '' && hash_equals($configuredHost, $requestHost)) {
                    URL::forceRootUrl($configuredUrl);
                }
            }
        }


        //
    }
}
