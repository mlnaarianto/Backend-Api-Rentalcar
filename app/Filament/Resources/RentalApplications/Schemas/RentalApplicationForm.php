<?php

namespace App\Filament\Resources\RentalApplications\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class RentalApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ===== KOLOM KIRI (span 2 dari 3): Info + Peta + Status =====
                Group::make([
                    Section::make('Informasi Pemohon & Usaha')
                        ->description('Data pemohon diambil otomatis dari akun terdaftar')
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

                            TextInput::make('business_name')
                                ->label('Nama Usaha Rental')
                                ->prefixIcon('heroicon-o-building-storefront')
                                ->disabled()
                                ->columnSpanFull(),

                            Textarea::make('business_address')
                                ->label('Alamat Usaha / Domisili')
                                ->rows(2)
                                ->disabled()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->collapsible(),

                    // 🗺️ TAMBAHAN: Section Peta Lokasi Usaha
                    Section::make('Peta Titik Lokasi Usaha')
                        ->description('Visualisasi titik koordinat yang ditandai oleh perental di peta')
                        ->icon('heroicon-o-map')
                        ->schema([
                            View::make('filament.resources.rental-application.map-view')
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),

                    Section::make('Status Verifikasi & Keputusan Admin')
                        ->description('Tentukan status akhir pengajuan ini')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Select::make('status')
                                ->label('Status Pengajuan')
                                ->options([
                                    'pending' => 'Pending (Menunggu)',
                                    'approved' => 'Approved (Disetujui)',
                                    'rejected' => 'Rejected (Ditolak)',
                                ])
                                ->native(false)
                                ->required()
                                ->columnSpan(1),

                            Textarea::make('admin_notes')
                                ->label('Catatan Admin')
                                ->rows(3)
                                ->placeholder('Opsional — isi jika status ditolak agar pemohon tahu alasannya')
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->columnSpan(2),

                // ===== KOLOM KANAN (span 1 dari 3): Dokumen KTP & SIM =====
                Section::make('Dokumen Verifikasi (KTP & SIM)')
                    ->description('Klik thumbnail untuk melihat gambar ukuran penuh')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        View::make('filament.resources.rental-application.view-documents')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpan(1),
            ]);
    }
}