<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\SurveiHarga;

class SkenarioNilaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user yang relevan
        $userPemohon = User::where('nik_user', 'C00524')->firstOrFail();
        $userKadiv = User::where('nik_user', '251785')->firstOrFail();
        $userGa = User::where('nik_user', 'C00424')->firstOrFail();
        $userBudget = User::where('nik_user', '171676')->firstOrFail();
        $userKadivOps = User::where('nik_user', '70981')->firstOrFail();

        // =======================================================================
        // SKENARIO 1: Nilai di bawah 100 Juta (Memerlukan Approval Direktur Operasional)
        // =======================================================================
        $this->command->info('Membuat skenario pengajuan di bawah 100 Juta...');

        $pengajuanA = Pengajuan::create([
            'id_user_pemohon' => $userPemohon->id_user,
            'kode_pengajuan' => 'REQ/SEED/OPS-001',
            'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            'kadiv_approved_by' => $userKadiv->id_user,
            'ga_surveyed_by' => $userGa->id_user,
            'budget_approved_by' => $userBudget->id_user,
            'kadiv_ops_budget_approved_by' => $userKadivOps->id_user,
            'budget_status_pengadaan' => 'Budget Tersedia',
        ]);

        $itemA = PengajuanItem::create([
            'id_pengajuan' => $pengajuanA->id_pengajuan,
            'kategori_barang' => 'Barang',
            'nama_barang' => 'Pengadaan 10 Unit PC All-in-One untuk CS',
            'kuantitas' => 10,
            'spesifikasi' => 'Core i5, RAM 8GB, SSD 256GB, Layar 21 inch.',
            'justifikasi' => 'Peremajaan komputer di bagian Customer Service.',
        ]);

        // Vendor termurah akan memiliki harga sekitar 8.5 Juta/unit (Total 85 Juta)
        SurveiHarga::create(['id_item' => $itemA->id_item, 'tipe_survei' => 'Pengadaan', 'nama_vendor' => 'Komputerindo', 'harga' => 8500000, 'kondisi_pajak' => 'Pajak ditanggung Vendor']);
        SurveiHarga::create(['id_item' => $itemA->id_item, 'tipe_survei' => 'Pengadaan', 'nama_vendor' => 'Jaya PC', 'harga' => 8750000, 'kondisi_pajak' => 'Pajak ditanggung Vendor']);


        // =======================================================================
        // SKENARIO 2: Nilai di bawah 5 Juta (Tidak Perlu Approval Direksi)
        // =======================================================================
        $this->command->info('Membuat skenario pengajuan di bawah 5 Juta...');

        $pengajuanB = Pengajuan::create([
            'id_user_pemohon' => $userPemohon->id_user,
            'kode_pengajuan' => 'REQ/SEED/KECIL-001',
            'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            'kadiv_approved_by' => $userKadiv->id_user,
            'ga_surveyed_by' => $userGa->id_user,
            'budget_approved_by' => $userBudget->id_user,
            'kadiv_ops_budget_approved_by' => $userKadivOps->id_user,
            'budget_status_pengadaan' => 'Budget Tersedia',
        ]);

        $itemB = PengajuanItem::create([
            'id_pengajuan' => $pengajuanB->id_pengajuan,
            'kategori_barang' => 'Barang',
            'nama_barang' => 'Pembelian Printer All-in-One',
            'kuantitas' => 1,
            'spesifikasi' => 'Printer Canon PIXMA G2010',
            'justifikasi' => 'Kebutuhan cetak dokumen untuk divisi HR.',
        ]);

        // Vendor termurah akan memiliki harga sekitar 2.1 Juta
        SurveiHarga::create(['id_item' => $itemB->id_item, 'tipe_survei' => 'Pengadaan', 'nama_vendor' => 'Toko Printer Cepat', 'harga' => 2100000, 'kondisi_pajak' => 'Tidak Ada Pajak']);
        SurveiHarga::create(['id_item' => $itemB->id_item, 'tipe_survei' => 'Pengadaan', 'nama_vendor' => 'Kertas dan Tinta', 'harga' => 2250000, 'kondisi_pajak' => 'Tidak Ada Pajak']);

        $this->command->info('Dua skenario pengajuan baru berhasil dibuat.');
    }
}
