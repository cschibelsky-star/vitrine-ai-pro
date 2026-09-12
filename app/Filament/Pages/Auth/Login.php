<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login-atlas';

    protected static string $layout = 'components.layouts.auth-atlas';

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('')
            ->hiddenLabel()
            ->placeholder('Usuário ou e-mail')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('')
            ->hiddenLabel()
            ->placeholder('Sua senha')
            ->password()
            ->revealable(false)
            ->required()
            ->autocomplete('current-password')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Lembrar de mim');
    }
}
