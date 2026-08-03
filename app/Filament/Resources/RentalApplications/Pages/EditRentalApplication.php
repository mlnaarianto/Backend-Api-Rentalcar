<?php

namespace App\Filament\Resources\RentalApplications\Pages;

use App\Filament\Resources\RentalApplications\RentalApplicationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRentalApplication extends EditRecord
{
    protected static string $resource = RentalApplicationResource::class;

    protected function resolveRecord($key): Model
    {
        // Ambil model dari resource terkait dan lakukan eager load
        return static::getResource()::getModel()::with('user', 'user.personalData')->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->color('warning')
                ->action('save'),

            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),

            DeleteAction::make(),
        ];
    }

    /**
     * Kosongkan supaya tombol Save/Cancel bawaan tidak dobel muncul di bawah form
     */
    protected function getFormActions(): array
    {
        return [];
    }
}