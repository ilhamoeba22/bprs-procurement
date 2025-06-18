<?php
namespace App\Filament\Resources\PengajuanResource\Pages;

use App\Filament\Resources\PengajuanResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Pengajuan;
use App\Models\User; 
use Illuminate\Support\Facades\Auth; 

class CreatePengajuan extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = PengajuanResource::class;

    /**
     * Fungsi ini akan berjalan tepat sebelum data disimpan ke database.
     * Di sinilah kita menambahkan logika cerdas untuk alur kerja.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $pemohon = Auth::user();

        // Cek apakah ada pengguna lain di divisi yang sama dengan peran 'Manager'
        $managerExists = User::where('id_divisi', $pemohon->id_divisi)
                              ->where('id_user', '!=', $pemohon->id_user) // Pastikan bukan dirinya sendiri
                              ->whereHas('roles', function ($query) {
                                  $query->where('name', 'Manager');
                              })
                              ->exists();

        // Atur status berikutnya secara dinamis berdasarkan hasil pengecekan
        if ($managerExists) {
            // Jika Manager ADA, kirim ke Manager untuk persetujuan pertama
            $data['status'] = Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER;
        } else {
            // Jika Manager TIDAK ADA, langsung lompati ke tahap Kepala Divisi
            $data['status'] = Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV;
        }

        return $data;
    }
}