<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\Deploy\PublicationControl::class, function () {
            return new \App\Services\Deploy\PublicationControl(
                new \App\Services\Deploy\HomologationGate(),
                new \App\Services\Deploy\ReleaseLedger(config('publication.ledger_directory')),
                fn (string $id, string $sha): array => app(\App\Services\Deploy\OperationalEvidence::class)->collect($id, $sha),
                fn (string $id): bool => \App\Models\User::whereKey($id)->where('role', 'admin')->where('is_active', true)->exists(),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
