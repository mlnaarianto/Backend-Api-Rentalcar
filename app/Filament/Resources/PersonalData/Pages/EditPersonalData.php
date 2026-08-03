<?php

namespace App\Filament\Resources\PersonalData\Pages;

use App\Filament\Resources\PersonalData\PersonalDataResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action; // 👈 Import Action untuk tombol kustom Cancel
use Filament\Resources\Pages\EditRecord;

class EditPersonalData extends EditRecord
{
    protected static string $resource = PersonalDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Cancel (Kembali ke halaman index tanpa menyimpan)
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(static::$resource::getUrl('index')),

            // Tombol Delete bawaan
            DeleteAction::make(),
        ];
    }

    /**
     * Menghilangkan tombol "Save Changes" (Submit) di bagian bawah/header form 
     * karena halaman ini hanya digunakan untuk melihat (read-only) data profil & dokumen.
     */
    protected function getFormActions(): array
    {
        return []; // Mengosongkan action form agar tombol Save hilang sepenuhnya
    }
}