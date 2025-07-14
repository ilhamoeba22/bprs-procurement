<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use Illuminate\Support\Facades\DB;

class PengadaanITSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user spesifik yang relevan
        $userPemohon = User::where('nik_user', 'C00524')->firstOrFail();
        $userKadiv = User::where('nik_user', '251785')->firstOrFail(); // Kadiv IT

        $this->command->info('Membuat skenario pengajuan Barang IT...');

        // 1. Buat Pengajuan Utama
        $pengajuan = Pengajuan::create([
            'id_user_pemohon' => $userPemohon->id_user,
            'kode_pengajuan' => 'REQ/IT/20250711/001',
            'status' => Pengajuan::STATUS_REKOMENDASI_IT, // Berhenti di tahap ini
            'catatan_revisi' => "[Disetujui oleh atasan]\nMohon rekomendasi dari tim IT untuk spesifikasi terbaik.",
            'manager_approved_by' => $userPemohon->atasan->id_user ?? null,
        ]);

        // 2. Buat Item-item Terkait
        PengajuanItem::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'kategori_barang' => '2a. Komputer & Hardware Sistem Informasi',
            'nama_barang' => 'Laptop untuk Tim Desain Grafis',
            'kuantitas' => 1,
            'spesifikasi' => 'Kebutuhan: RAM min. 16GB, VGA dedicated (Nvidia/AMD), Layar 15 inch dengan akurasi warna tinggi.',
            'justifikasi' => 'Laptop saat ini sudah lambat untuk menjalankan aplikasi desain terbaru.',
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'kategori_barang' => '1a. Software',
            'nama_barang' => 'Lisensi Software Antivirus (Tahunan)',
            'kuantitas' => 10,
            'spesifikasi' => 'Lisensi untuk 10 user, kompatibel dengan Windows 11.',
            'justifikasi' => 'Lisensi antivirus saat ini akan segera berakhir.',
        ]);

        $this->command->info('Skenario Pengadaan IT berhasil dibuat.');
    }
}
