<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Events\Registered;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Client created';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
            $data['user_id'] = \Illuminate\Support\Facades\Auth::id();        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = parent::handleRecordCreation($data);

        // Optionally dispatch the Registered event if needed for notifications
        // Registered::dispatch($user);

        return $user;
    }
}