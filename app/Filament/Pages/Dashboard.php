<?php 



namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ActiveTripsWidget;
use App\Filament\Widgets\AvailabilityWidget;
use App\Filament\Widgets\MonthlyTripsWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            ActiveTripsWidget::class,
            AvailabilityWidget::class,
            MonthlyTripsWidget::class,
        ];
    }
}
