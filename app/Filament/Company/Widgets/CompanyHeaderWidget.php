<?php

namespace App\Filament\Company\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CompanyHeaderWidget extends Widget
{
    protected static ?string $pollingInterval = null;

    protected static ?string $name = 'Company Header';

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        $user = Auth::user();
        if ($user && $user->company) {
            return 'Welcome to ' . $user->company->name . ' Admin';  // Unique company name
        }
        return 'Company Admin Panel';
    }
}