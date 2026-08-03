<?php

namespace App\Filament\Resources\PersonalData\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables;
use Filament\Tables\Table;

class PersonalDataTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sim_number')
                    ->label('Nomor SIM')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sim_type')
                    ->label('Gol. SIM'),
                Tables\Columns\TextColumn::make('sim_expired_date')
                    ->label('Masa Berlaku SIM')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tambahkan filter golongan SIM atau tanggal jika diperlukan
            ])
            ->actions([
                EditAction::make()->label('Review Profil & SIM'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}