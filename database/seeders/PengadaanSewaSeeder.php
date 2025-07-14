<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\SurveiHarga;
use Illuminate\Support\Facades\DB;

class PengadaanSewaSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user spesifik yang relevan
        $userPemohon = User::where('nik_user', 'C00524')->firstOrFail();
        $userKadivGa = User::where('nik_user', '411881')->firstOrFail();
        $userBudget = User::where('nik_user', '171676')->firstOrFail();
        $userKadivOps = User::where('nik_user', '70981')->firstOrFail();

        $this->command->info('Membuat skenario pengajuan Sewa & Pengadaan Kantor...');

        // 1. Buat Pengajuan Utama
        $pengajuan = Pengajuan::create([
            'id_user_pemohon' => $userPemohon->id_user,
            'kode_pengajuan' => 'REQ/KANCAB/20250711/001',
            'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            'catatan_revisi' => "Budget telah divalidasi oleh Kadiv Operasional. Mohon Kadiv GA untuk membuat keputusan akhir.",
            'kadiv_approved_by' => $userPemohon->atasan->id_user ?? null, // Asumsi atasan langsung
            'ga_surveyed_by' => User::where('nik_user', 'C00424')->first()->id_user,
            'budget_approved_by' => $userBudget->id_user,
            'kadiv_ops_budget_approved_by' => $userKadivOps->id_user,
            'budget_status_pengadaan' => 'Budget Tersedia',
            'budget_catatan_pengadaan' => 'Sesuai RBB tahun ini.',
        ]);

        // 2. Buat Item Sewa dan Barang
        $itemSewa = PengajuanItem::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'kategori_barang' => 'Sewa',
            'nama_barang' => 'Sewa Ruko untuk Kantor Cabang',
            'kuantitas' => 1,
            'spesifikasi' => 'Lokasi di Jalan Margonda Raya, 2 lantai, luas 150m2. Sewa untuk 2 tahun.',
            'justifikasi' => 'Ekspansi pasar dan pembukaan kantor cabang baru di Depok.',
        ]);

        $itemBarang = PengajuanItem::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'kategori_barang' => 'Barang',
            'nama_barang' => 'Furnitur Kantor (Meja & Kursi)',
            'kuantitas' => 5,
            'spesifikasi' => '5 set meja dan kursi kerja standar.',
            'justifikasi' => 'Kebutuhan dasar untuk operasional kantor cabang baru.',
        ]);

        // 3. Buat Data Survei Harga
        // Vendor untuk Sewa Ruko
        SurveiHarga::create([
            'id_item' => $itemSewa->id_item,
            'tipe_survei' => 'Pengadaan',
            'nama_vendor' => 'Pemilik Ruko Bpk. H. Ahmad',
            'harga' => 150000000,
            'rincian_harga' => 'Harga sewa untuk 2 tahun.',
            'kondisi_pajak' => 'Pajak ditanggung kita',
            'jenis_pajak' => 'PPh 23',
            'npwp_nik' => '112233445566778',
            'nama_pemilik_pajak' => 'H. Ahmad Subarjo',
            'nominal_pajak' => 15000000, // PPh Final Sewa 10%
        ]);

        // Vendor untuk Furnitur
        SurveiHarga::create([
            'id_item' => $itemBarang->id_item,
            'tipe_survei' => 'Pengadaan',
            'nama_vendor' => 'Informa Margonda',
            'harga' => 2500000,
            'rincian_harga' => 'Harga per set meja & kursi.',
            'kondisi_pajak' => 'Pajak ditanggung Vendor',
        ]);
        SurveiHarga::create([
            'id_item' => $itemBarang->id_item,
            'tipe_survei' => 'Pengadaan',
            'nama_vendor' => 'IKEA Kota Wisata',
            'harga' => 2750000,
            'rincian_harga' => 'Harga per set meja & kursi.',
            'kondisi_pajak' => 'Pajak ditanggung Vendor',
        ]);

        $this->command->info('Skenario Sewa & Pengadaan Kantor berhasil dibuat.');
    }
}
