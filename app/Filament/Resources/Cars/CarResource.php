<?php

namespace App\Filament\Resources\Cars;

use App\Filament\Resources\Cars\Pages;
use App\Models\Car;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Mobil';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Mobil')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('brand')
                    ->label('Merek')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('plate_number')
                    ->label('No. Plat')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('engine_type')
                    ->label('Tipe Mesin')
                    ->required(),

                Forms\Components\TextInput::make('fuel_spec')
                    ->label('Spesifikasi BBM'),

                Forms\Components\TextInput::make('seats')
                    ->label('Jumlah Kursi')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->length(4)
                    ->required(),

                Forms\Components\TextInput::make('price_per_day')
                    ->label('Harga / Hari')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\TextInput::make('driver_price_per_day')
                    ->label('Harga Sopir / Hari')
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image')
                    ->label('Foto Mobil')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('cars')
                    // Live preview thumbnail dimatikan karena request preview URL
                    // (Livewire temporary URL) bisa gagal/stuck kalau host akses
                    // browser (mis. localhost) beda dengan APP_URL (mis. IP LAN).
                    // Upload & simpan data tetap normal walau preview dimatikan.
                    ->previewable(false)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('video_url')
                    ->label('Link Video (YouTube)')
                    ->url()
                    ->maxLength(255),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'tersedia'  => 'Tersedia',
                        'disewa'    => 'Disewa',
                        'perbaikan' => 'Perbaikan',
                    ])
                    ->default('tersedia'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('image')
                    ->label('Foto')
                    ->html()
                    ->formatStateUsing(fn ($state) =>
                        $state
                            ? '<img src="' . htmlspecialchars($state) . '" class="w-16 h-16 rounded-md object-cover shadow-sm" />'
                            : '-'
                    ),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable(),

                Tables\Columns\TextColumn::make('plate_number')
                    ->label('No. Plat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('engine_type')
                    ->label('Mesin'),

                Tables\Columns\TextColumn::make('seats')
                    ->label('Kursi'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun'),

                Tables\Columns\TextColumn::make('price_per_day')
                    ->label('Harga/Hari')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('driver_price_per_day')
                    ->label('Harga Sopir/Hari')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'tersedia',
                        'warning' => 'disewa',
                        'danger'  => 'perbaikan',
                    ]),

                Tables\Columns\TextColumn::make('video_url')
                    ->label('Video')
                    ->formatStateUsing(fn ($state) => $state ? 'Tonton ▶' : '-')
                    ->color('primary')
                    ->action(
                        Actions\Action::make('tonton_video')
                            ->label('Tonton Video')
                            ->icon('heroicon-o-play-circle')
                            ->visible(fn ($record) => filled($record->video_url))
                            ->modalHeading(fn ($record) => "Video Review - {$record->name}")
                            ->modalContent(fn ($record) => view('filament.infolists.video-embed', [
                                'getRecord' => fn () => $record,
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('4xl')
                    ),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pemilik')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'tersedia'  => 'Tersedia',
                        'disewa'    => 'Disewa',
                        'perbaikan' => 'Perbaikan',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Mobil')
                    ->schema([
                        TextEntry::make('image')
                            ->label('Foto')
                            ->html()
                            ->formatStateUsing(fn ($state) =>
                                $state
                                    ? '<img src="' . htmlspecialchars($state) . '" class="w-32 h-auto rounded-lg object-cover shadow-md" />'
                                    : '-'
                            )
                            ->columnSpanFull(),

                        TextEntry::make('name')->label('Nama'),
                        TextEntry::make('brand')->label('Merek'),
                        TextEntry::make('plate_number')->label('No. Plat'),
                        TextEntry::make('engine_type')->label('Tipe Mesin'),
                        TextEntry::make('fuel_spec')->label('Spesifikasi BBM'),
                        TextEntry::make('seats')->label('Jumlah Kursi'),
                        TextEntry::make('year')->label('Tahun'),
                        TextEntry::make('price_per_day')->label('Harga/Hari')->money('IDR'),
                        TextEntry::make('driver_price_per_day')->label('Harga Sopir/Hari')->money('IDR'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
                        TextEntry::make('user.name')->label('Pemilik'),
                    ])
                    ->columns(2),

                Section::make('Video')
                    ->schema([
                        TextEntry::make('video_url')
                            ->label('')
                            ->view('filament.infolists.video-embed'),
                    ])
                    ->visible(fn ($record) => filled($record->video_url)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit'   => Pages\EditCar::route('/{record}/edit'),
            'view'   => Pages\ViewCar::route('/{record}'),
        ];
    }
}