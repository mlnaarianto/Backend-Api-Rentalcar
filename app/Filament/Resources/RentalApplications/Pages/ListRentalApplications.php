<?php

namespace App\Filament\Resources\RentalApplications\Pages;

use App\Filament\Resources\RentalApplications\RentalApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentalApplications extends ListRecords
{
    protected static string $resource = RentalApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
