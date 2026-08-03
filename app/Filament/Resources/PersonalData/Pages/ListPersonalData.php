<?php

namespace App\Filament\Resources\PersonalData\Pages;

use App\Filament\Resources\PersonalData\PersonalDataResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalData extends ListRecords
{
    protected static string $resource = PersonalDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
