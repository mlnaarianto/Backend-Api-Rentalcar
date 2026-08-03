<?php

namespace App\Filament\Resources\RentalApplications;

use App\Filament\Resources\RentalApplications\Pages\EditRentalApplication;
use App\Filament\Resources\RentalApplications\Pages\ListRentalApplications;
use App\Filament\Resources\RentalApplications\Schemas\RentalApplicationForm;
use App\Filament\Resources\RentalApplications\Tables\RentalApplicationsTable;
use App\Models\RentalApplication;
use BackedEnum; // 👈 Pastikan BackedEnum di-import
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RentalApplicationResource extends Resource
{
    protected static ?string $model = RentalApplication::class;

    // 👈 Sesuaikan tipe datanya dengan parent class (BackedEnum|string|null)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Verifikasi Perental';
    protected static ?string $pluralModelLabel = 'Pengajuan Perental';

    public static function form(Schema $schema): Schema
    {
        return RentalApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RentalApplicationsTable::configure($table);
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
            'index' => ListRentalApplications::route('/'),
            'edit' => EditRentalApplication::route('/{record}/edit'),
        ];
    }
}