<?php

namespace App\Filament\Resources\Cars\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('brand')
                    ->required(),
                TextInput::make('plate_number')
                    ->required(),
                Select::make('engine_type')
                    ->options([
            'Bensin (Gasoline)' => 'Bensin( gasoline)',
            'Diesel (Gasoil)' => 'Diesel( gasoil)',
            'Listrik (Electric / EV)' => 'Listrik( electric/ e v)',
            'Hybrid (HEV / PHEV)' => 'Hybrid( h e v/ p h e v)',
        ])
                    ->required(),
                TextInput::make('fuel_spec'),
                TextInput::make('seats')
                    ->required()
                    ->numeric(),
                TextInput::make('year')
                    ->required(),
                TextInput::make('price_per_day')
                    ->required()
                    ->numeric(),
                TextInput::make('driver_price_per_day')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->image(),
                TextInput::make('video_url')
                    ->url(),
                Select::make('status')
                    ->options(['tersedia' => 'Tersedia', 'disewa' => 'Disewa', 'perbaikan' => 'Perbaikan'])
                    ->default('tersedia')
                    ->required(),
            ]);
    }
}
