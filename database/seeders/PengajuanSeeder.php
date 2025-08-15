<?php

namespace Database\Seeders;

use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PengajuanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil data pengguna
        $pemohon = User::where('nik_user', 'C00424')->firstOrFail();
        $kadiv = User::where('nik_user', '411881')->firstOrFail();

        // Pastikan kadiv memiliki role Kepala Divisi
        if (!$kadiv->hasRole('Kepala Divisi')) {
            $kadiv->assignRole('Kepala Divisi');
        }

        // 2. Generate kode pengajuan
        $date = Carbon::today()->format('Ymd');
        $kodeKantor = $pemohon->kantor->kode_kantor ?? 'XXX';

        // Pengajuan 1: Hanya Barang
        $pengajuan1 = Pengajuan::create([
            'kode_pengajuan' => "REQ/{$kodeKantor}/C00424/{$date}/001",
            'id_user_pemohon' => $pemohon->id_user,
            'status' => Pengajuan::STATUS_SURVEI_GA,
            'kadiv_approved_by' => $kadiv->id_user,
            'catatan_revisi' => 'Disetujui oleh Kepala Divisi: ' . $kadiv->nama_user . ' pada ' . now()->format('d-m-Y H:i'),
            'total_nilai' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan1->id_pengajuan,
            'kategori_barang' => '2d. Perlengkapan Kantor Lainnya',
            'nama_barang' => 'Meja Kerja',
            'kuantitas' => 5,
            'spesifikasi' => 'Meja kerja kayu dengan ukuran 120x60 cm, warna cokelat tua',
            'justifikasi' => 'Untuk melengkapi ruang kerja karyawan baru di kantor.',
        ]);

        // Pengajuan 2: Barang dan Jasa
        $pengajuan2 = Pengajuan::create([
            'kode_pengajuan' => "REQ/{$kodeKantor}/C00424/{$date}/002",
            'id_user_pemohon' => $pemohon->id_user,
            'status' => Pengajuan::STATUS_SURVEI_GA,
            'kadiv_approved_by' => $kadiv->id_user,
            'catatan_revisi' => 'Disetujui oleh Kepala Divisi: ' . $kadiv->nama_user . ' pada ' . now()->format('d-m-Y H:i'),
            'total_nilai' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan2->id_pengajuan,
            'kategori_barang' => '2b. Peralatan atau Mesin Kantor',
            'nama_barang' => 'Mesin Fotokopi',
            'kuantitas' => 1,
            'spesifikasi' => 'Mesin fotokopi multifungsi, mampu mencetak dan memindai hingga ukuran A3',
            'justifikasi' => 'Untuk kebutuhan dokumentasi dan pengarsipan dokumen kantor.',
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan2->id_pengajuan,
            'kategori_barang' => 'Jasa',
            'nama_barang' => 'Jasa Kebersihan Kantor',
            'kuantitas' => 1,
            'spesifikasi' => 'Jasa kebersihan harian untuk ruang kantor seluas 200 m²',
            'justifikasi' => 'Untuk menjaga kebersihan dan kenyamanan lingkungan kerja.',
        ]);

        // Pengajuan 3: Barang, Jasa, dan Sewa
        $pengajuan3 = Pengajuan::create([
            'kode_pengajuan' => "REQ/{$kodeKantor}/C00424/{$date}/003",
            'id_user_pemohon' => $pemohon->id_user,
            'status' => Pengajuan::STATUS_SURVEI_GA,
            'kadiv_approved_by' => $kadiv->id_user,
            'catatan_revisi' => 'Disetujui oleh Kepala Divisi: ' . $kadiv->nama_user . ' pada ' . now()->format('d-m-Y H:i'),
            'total_nilai' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan3->id_pengajuan,
            'kategori_barang' => '2c. Kendaraan Bermotor',
            'nama_barang' => 'Mobil Operasional',
            'kuantitas' => 1,
            'spesifikasi' => 'Mobil sedan 1500 cc, tahun produksi 2023, warna hitam',
            'justifikasi' => 'Untuk kebutuhan transportasi operasional tim lapangan.',
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan3->id_pengajuan,
            'kategori_barang' => 'Jasa',
            'nama_barang' => 'Jasa Katering Acara',
            'kuantitas' => 1,
            'spesifikasi' => 'Katering untuk 50 orang, termasuk makanan utama, camilan, dan minuman',
            'justifikasi' => 'Untuk mendukung acara pelatihan internal perusahaan.',
        ]);

        PengajuanItem::create([
            'id_pengajuan' => $pengajuan3->id_pengajuan,
            'kategori_barang' => 'Sewa',
            'nama_barang' => 'Sewa Ruang Pertemuan',
            'kuantitas' => 1,
            'spesifikasi' => 'Ruang pertemuan untuk 50 orang selama 1 hari, dilengkapi proyektor dan sound system',
            'justifikasi' => 'Untuk kebutuhan acara pelatihan internal perusahaan.',
        ]);
    }
}
