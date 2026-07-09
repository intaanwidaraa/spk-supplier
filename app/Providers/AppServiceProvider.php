<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make(
                'spk-login-style',
                asset('css/spk-login.css')
            ),
        ]);

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View => view(
                'filament.auth.login-security'
            ),
            scopes: Login::class,
        );
    }
}
