<?php

namespace App\Filament\Resources\Cars\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CarInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('brand'),
                TextEntry::make('plate_number'),
                TextEntry::make('engine_type')
                    ->badge(),
                TextEntry::make('fuel_spec')
                    ->placeholder('-'),
                TextEntry::make('seats')
                    ->numeric(),
                TextEntry::make('year'),
                TextEntry::make('price_per_day')
                    ->numeric(),
                TextEntry::make('driver_price_per_day')
                    ->numeric(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                ImageEntry::make('image')
                    ->placeholder('-'),
                TextEntry::make('video_url')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
