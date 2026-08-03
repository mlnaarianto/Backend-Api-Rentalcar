<?php

namespace App\Filament\Resources\PersonalData;

use App\Filament\Resources\PersonalData\Pages\EditPersonalData;
use App\Filament\Resources\PersonalData\Pages\ListPersonalData;
use App\Filament\Resources\PersonalData\Schemas\PersonalDataForm;
use App\Filament\Resources\PersonalData\Tables\PersonalDataTable;
use App\Models\PersonalData;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PersonalDataResource extends Resource
{
    protected static ?string $model = PersonalData::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Verifikasi Profil & SIM';
    protected static ?string $pluralModelLabel = 'Verifikasi Profil & SIM';

    public static function form(Schema $schema): Schema
    {
        return PersonalDataForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalDataTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalData::route('/'),
            'edit' => EditPersonalData::route('/{record}/edit'),
        ];
    }
}