<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about-master', function () {
    $this->info('Vitrine AI Pro Master Start MVP');
});

// Reconcile expired Assessor Online evaluations and pending access revocations.
// Registered but never executed outside the isolated, explicitly enabled HML.
// A scheduler process must be configured independently after HML approval.
\Illuminate\Support\Facades\Schedule::command('assessor:trial expire')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->when(fn (): bool => app()->environment('hml') && config('assessor_trial.enabled') === true);
