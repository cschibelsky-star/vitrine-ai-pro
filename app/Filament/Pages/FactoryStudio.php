<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FactoryStudio extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Factory Studio';

    protected static string $view = 'filament.pages.factory-studio';
}
