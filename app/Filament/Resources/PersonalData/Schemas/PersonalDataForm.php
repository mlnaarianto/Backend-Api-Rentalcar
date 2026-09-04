<?php

namespace App\Filament\Resources\PersonalData\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PersonalDataForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ===== KOLOM KIRI (span 2 dari 3): Informasi Akun, Kontak, & Data SIM =====
                Group::make([
                    Section::make('Informasi Akun & Kontak')
                        ->description('Data dasar akun user yang terdaftar')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('user_name')
                                        ->label('Nama Lengkap')
                                        ->prefixIcon('heroicon-o-identification')
                                        ->afterStateHydrated(fn (TextInput $component, $record) =>
                                            $component->state($record?->user?->name))
                                        ->disabled(),

                                    TextInput::make('user_email')
                                        ->label('Email')
                                        ->prefixIcon('heroicon-o-envelope')
                                        ->afterStateHydrated(fn (TextInput $component, $record) =>
                                            $component->state($record?->user?->email))
                                        ->disabled(),
                                ]),

                            TextInput::make('phone')
                                ->label('Nomor HP / WhatsApp')
                                ->prefixIcon('heroicon-o-phone')
                                ->disabled()
                                ->columnSpanFull(),

                            Textarea::make('address')
                                ->label('Alamat Lengkap')
                                ->rows(2)
                                ->disabled()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->collapsible(),

                    Section::make('Informasi Surat Izin Mengemudi (SIM)')
                        ->description('Detail nomor, golongan, dan masa berlaku SIM')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('sim_number')
                                        ->label('Nomor SIM')
                                        ->disabled(),

                                    TextInput::make('sim_type')
                                        ->label('Golongan SIM')
                                        ->disabled(),

                                    TextInput::make('sim_expired_date')
                                        ->label('Masa Berlaku SIM')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => $state
                                            ? \Carbon\Carbon::parse($state)->translatedFormat('d F Y')
                                            : '-'),
                                ]),
                        ])
                        ->columns(1),
                ])
                ->columnSpan(2),

                // ===== KOLOM KANAN (span 1 dari 3): Dokumen KTP & SIM =====
                Section::make('Dokumen Verifikasi (KTP & SIM)')
                    ->description('Klik thumbnail untuk melihat gambar ukuran penuh')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        View::make('filament.resources.personal-data.view-documents')
                            ->columnSpan('full'),
                    ])
                    ->collapsible()
                    ->columnSpan(1),
            ]);
    }
}