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
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ActiveTripsWidget;
use App\Filament\Widgets\AvailabilityWidget;
use App\Filament\Widgets\DriverStatsOverviewWidget;
use App\Filament\Widgets\MonthlyTripsWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // 🎨 Theme + Primary Color
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/Admin/theme.css')
            // 📦 Auto-discovery
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
             ->discoverResources(in: app_path('Filament/PAGES'), for: 'App\\Filament\\PAGES')
            ->discoverPages(in: app_path('Filament/Admin'), for: 'App\\Filament\\Admin')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // 📊 Pages
            ->pages([
                Pages\Dashboard::class,

            ])
            // 📌 Widgets
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                ActiveTripsWidget::class,
                AvailabilityWidget::class,
                DriverStatsOverviewWidget::class,
                MonthlyTripsWidget::class,
            ])
            // 🔒 Middleware
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