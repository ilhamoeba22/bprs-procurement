<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\SurveiHarga;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengecatanKantorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user spesifik yang sudah ada dari UserSeeder
        $userPemohon = User::where('nik_user', 'C00524')->firstOrFail();
        $userKadiv = User::where('nik_user', '251785')->firstOrFail();
        $userGa = User::where('nik_user', 'C00424')->firstOrFail();
        $userBudget = User::where('nik_user', '171676')->firstOrFail();

        $this->command->info('Membuat skenario pengajuan Pengecatan Kantor...');

        // 1. Buat Pengajuan Utama
        $pengajuan = Pengajuan::create([
            'id_user_pemohon' => $userPemohon->id_user,
            'kode_pengajuan' => 'REQ/PAINT/20250711/001',
            'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
            'catatan_revisi' => "[Disetujui oleh Kadiv: {$userKadiv->nama_user}]\nLanjutkan dengan mencari vendor terbaik.",
            'kadiv_approved_by' => $userKadiv->id_user,
            'ga_surveyed_by' => $userGa->id_user,
        ]);

        // 2. Buat Item-item Terkait (Barang, Jasa, Sewa)
        $itemCat = PengajuanItem::create(['id_pengajuan' => $pengajuan->id_pengajuan, 'kategori_barang' => 'Barang', 'nama_barang' => 'Cat Tembok Interior (25 Liter)', 'kuantitas' => 2, 'spesifikasi' => 'Merk Dulux Catylac, Warna Putih Salju, Kemasan Pail.', 'justifikasi' => 'Kebutuhan untuk pengecatan ulang dinding interior kantor lantai 2.']);
        $itemJasa = PengajuanItem::create(['id_pengajuan' => $pengajuan->id_pengajuan, 'kategori_barang' => 'Jasa', 'nama_barang' => 'Jasa Pengecatan Tembok', 'kuantitas' => 1, 'spesifikasi' => 'Pengecatan 2 lapis untuk area seluas 150 m2, termasuk pembersihan awal.', 'justifikasi' => 'Membutuhkan tenaga ahli untuk hasil yang rapi dan cepat.']);
        $itemSewa = PengajuanItem::create(['id_pengajuan' => $pengajuan->id_pengajuan, 'kategori_barang' => 'Sewa', 'nama_barang' => 'Sewa Scaffolding (Perancah)', 'kuantitas' => 3, 'spesifikasi' => 'Sewa 3 set scaffolding untuk durasi 5 hari kerja.', 'justifikasi' => 'Diperlukan untuk menjangkau area dinding yang tinggi dengan aman.']);

        // 3. Definisikan Vendor dengan detail pembayaran & pajak yang bervariasi
        $vendors = [
            [
                'nama' => 'CV. Karya Mandiri',
                'npwp' => '987654321098765',
                'pemilik' => 'Bapak Susanto',
                'metode_pembayaran' => 'Transfer',
                'opsi_pembayaran' => 'Bisa DP',
                'nama_bank' => 'BCA',
                'no_rekening' => '8880123456',
                'nama_rekening' => 'CV Karya Mandiri',
            ],
            [
                'nama' => 'Toko Cat Pelangi Jaya',
                'npwp' => '123456789012345',
                'pemilik' => 'Ibu Wati',
                'metode_pembayaran' => 'Transfer',
                'opsi_pembayaran' => 'Langsung Lunas',
                'nama_bank' => 'Mandiri',
                'no_rekening' => '1310098765432',
                'nama_rekening' => 'Toko Cat Pelangi Jaya',
            ]
        ];

        // 4. Buat Data Survei Harga yang Detail untuk setiap vendor
        foreach ($vendors as $vendor) {
            // --- Survei untuk Cat Tembok ---
            SurveiHarga::create([
                'id_item' => $itemCat->id_item,
                'tipe_survei' => 'Pengadaan',
                'nama_vendor' => $vendor['nama'],
                'harga' => rand(800000, 950000),
                'rincian_harga' => 'Harga per Pail 25 Liter.',
                'kondisi_pajak' => 'Tidak Ada Pajak', // Barang diasumsikan tidak kena PPh
                // Data pembayaran
                'metode_pembayaran' => $vendor['metode_pembayaran'],
                'opsi_pembayaran' => $vendor['opsi_pembayaran'],
                'nama_bank' => $vendor['nama_bank'],
                'no_rekening' => $vendor['no_rekening'],
                'nama_rekening' => $vendor['nama_rekening'],
            ]);

            // --- Survei untuk Jasa Tukang Cat ---
            $hargaJasa = rand(2500000, 3000000);
            SurveiHarga::create([
                'id_item' => $itemJasa->id_item,
                'tipe_survei' => 'Pengadaan',
                'nama_vendor' => $vendor['nama'],
                'harga' => $hargaJasa,
                'rincian_harga' => 'Harga borongan untuk seluruh area, sudah termasuk alat kerja dasar.',
                // Skenario Pajak Bervariasi
                'kondisi_pajak' => $vendor['nama'] === 'CV. Karya Mandiri' ? 'Pajak ditanggung kita' : 'Pajak ditanggung Vendor',
                'jenis_pajak' => 'PPh 23',
                'npwp_nik' => $vendor['npwp'],
                'nama_pemilik_pajak' => $vendor['pemilik'],
                'nominal_pajak' => $vendor['nama'] === 'CV. Karya Mandiri' ? $hargaJasa * 0.02 : null, // Hanya dihitung jika ditanggung perusahaan
                // Data pembayaran
                'metode_pembayaran' => $vendor['metode_pembayaran'],
                'opsi_pembayaran' => $vendor['opsi_pembayaran'],
                'nama_bank' => $vendor['nama_bank'],
                'no_rekening' => $vendor['no_rekening'],
                'nama_rekening' => $vendor['nama_rekening'],
            ]);

            // --- Survei untuk Sewa Scaffolding ---
            $hargaSewa = rand(100000, 150000) * 5;
            SurveiHarga::create([
                'id_item' => $itemSewa->id_item,
                'tipe_survei' => 'Pengadaan',
                'nama_vendor' => $vendor['nama'],
                'harga' => $hargaSewa,
                'rincian_harga' => 'Harga sewa untuk 5 hari kerja.',
                'kondisi_pajak' => 'Pajak ditanggung kita', // Sewa alat selalu kena PPh
                'jenis_pajak' => 'PPh 23',
                'npwp_nik' => $vendor['npwp'],
                'nama_pemilik_pajak' => $vendor['pemilik'],
                'nominal_pajak' => $hargaSewa * 0.02,
                // Data pembayaran
                'metode_pembayaran' => $vendor['metode_pembayaran'],
                'opsi_pembayaran' => $vendor['opsi_pembayaran'],
                'nama_bank' => $vendor['nama_bank'],
                'no_rekening' => $vendor['no_rekening'],
                'nama_rekening' => $vendor['nama_rekening'],
            ]);
        }

        $this->command->info('Skenario Pengecatan Kantor berhasil dibuat dengan detail lengkap.');
    }
}
