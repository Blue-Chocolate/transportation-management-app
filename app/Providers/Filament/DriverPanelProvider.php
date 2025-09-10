<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DriverPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
    ->id('driver')
    ->brandName('Driver Portal')
    ->path('driver')
    ->authGuard('driver')
    ->login()
    ->passwordReset()
    ->profile()
    ->resources([
        \App\Filament\Driver\Resources\TripsResource::class,
        \App\Filament\Driver\Resources\DriverProfileResource::class,
    ])
    ->colors(['primary' => Color::Amber])
    ->styles([asset('css/filament/driver/theme.css')])
    ->pages([Pages\Dashboard::class])
    ->widgets([
        Widgets\AccountWidget::class,
        Widgets\FilamentInfoWidget::class,
        \App\Filament\Driver\Widgets\DriverTripsWidget::class,
            ])
             ->favicon(asset('images/driver-favicon.ico'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Driver/Resources'), for: 'App\\Filament\\Driver\\Resources')
            ->discoverPages(in: app_path('Filament/Driver/Pages'), for: 'App\\Filament\\Driver\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Driver/Widgets'), for: 'App\\Filament\\Driver\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
