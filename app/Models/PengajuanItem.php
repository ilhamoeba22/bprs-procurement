<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_item';
    protected $fillable = ['id_pengajuan', 'kategori_barang', 'nama_barang', 'kuantitas', 'spesifikasi', 'justifikasi', 'harga_final'];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function surveiHargas(): HasMany
    {
        return $this->hasMany(SurveiHarga::class, 'id_item');
    }

    public function surveiPengadaan(): HasMany
    {
        return $this->surveiHargas()->where('tipe_survei', 'Pengadaan');
    }

    public function surveiPerbaikan(): HasMany
    {
        return $this->surveiHargas()->where('tipe_survei', 'Perbaikan');
    }

    public function vendorFinal(): HasOne
    {
        return $this->hasOne(SurveiHarga::class, 'id_item')->where('is_final', 1);
    }
}
