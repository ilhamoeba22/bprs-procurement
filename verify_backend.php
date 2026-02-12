<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\SurveiHarga;
use Illuminate\Support\Str;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "--- Testing GA Query Logic ---\n";
    $selesaiCount = Pengajuan::where('status', Pengajuan::STATUS_SELESAI)->count();
    $ditolakCount = Pengajuan::where('status', 'like', '%Ditolak%')->count();
    echo "Selesai: $selesaiCount, Ditolak: $ditolakCount\n";

    echo "\n--- Testing Cascading Delete ---\n";
    $p = new Pengajuan();
    $p->kode_pengajuan = 'TC-' . Str::random(5);
    $p->id_user_pemohon = 1;
    $p->status = 'Draft';
    $p->save();
    
    $item = new PengajuanItem();
    $item->id_pengajuan = $p->id_pengajuan;
    $item->nama_barang = 'Test';
    $item->spesifikasi = 'Test';
    $item->kuantitas = 1;
    $item->kategori_barang = 'Barang';
    $item->save();

    echo "Initial records saved. ID: {$p->id_pengajuan}\n";
    
    $id = $p->id_pengajuan;
    $p->delete();
    echo "Pengajuan deleted.\n";
    
    $remainingItems = PengajuanItem::where('id_pengajuan', $id)->count();
    echo "Remaining items: $remainingItems (Expected 0)\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " LINE: " . $e->getLine() . "\n";
}
