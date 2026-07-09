<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Masuk ke SPK-SUPPLIER';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Selamat Datang Kembali';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Masuk untuk mengelola data supplier, proses evaluasi, perhitungan, dan laporan.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->placeholder('nama@perusahaan.com')
            ->prefixIcon('heroicon-m-envelope')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->placeholder('Masukkan kata sandi')
            ->prefixIcon('heroicon-m-lock-closed')
            ->password()
            ->revealable(
                filament()->arePasswordsRevealable()
            )
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes([
                'tabindex' => 2,
            ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya di perangkat ini');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk ke Sistem')
            ->icon('heroicon-m-arrow-right-on-rectangle')
            ->iconPosition('after')
            ->submit('authenticate');
    }
}