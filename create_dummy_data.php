<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use Illuminate\Support\Str;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function createDummy($status, $pemohon) {
    $p = Pengajuan::create([
        'kode_pengajuan' => 'DUMMY/' . date('Ymd') . '/' . Str::upper(Str::random(5)),
        'id_user_pemohon' => $pemohon->id_user,
        'status' => $status,
        'total_nilai' => rand(1000000, 5000000),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PengajuanItem::create([
        'id_pengajuan' => $p->id_pengajuan,
        'nama_barang' => 'Barang Dummy ' . $status,
        'spesifikasi' => 'Spek dummy',
        'kuantitas' => 1,
        'kategori_barang' => 'Barang',
        'justifikasi' => 'Justifikasi dummy',
    ]);
    return $p;
}

$superAdmin = User::role('Super Admin')->first();
$gaUser = User::role('General Affairs')->first();
$pemohon = User::first(); // Just use anyone as pemohon

if (!$superAdmin || !$gaUser) {
    echo "Missing Super Admin or GA user roles.\n";
    exit;
}

echo "Super Admin: {$superAdmin->nama_user} (ID: {$superAdmin->id_user})\n";
echo "GA User: {$gaUser->nama_user} (ID: {$gaUser->id_user})\n";

createDummy(Pengajuan::STATUS_SELESAI, $pemohon);
createDummy(Pengajuan::STATUS_DITOLAK_MANAGER, $pemohon);
createDummy(Pengajuan::STATUS_SURVEI_GA, $pemohon);

echo "Dummy data created successfully.\n";
