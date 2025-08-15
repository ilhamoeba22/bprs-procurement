<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveiHarga extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_item',
        'tipe_survei',
        'nama_vendor',
        'harga',
        'bukti_path',
        'kondisi_pajak',
        'jenis_pajak',
        'npwp_nik',
        'nama_pemilik_pajak',
        'nominal_pajak',
        'rincian_harga',
    ];

    public function pengajuanItem(): BelongsTo
    {
        return $this->belongsTo(PengajuanItem::class, 'id_item');
    }

    public function revisiHargas(): HasMany
    {
        return $this->hasMany(RevisiHarga::class, 'survei_harga_id');
    }

    public function latestRevisi(): ?RevisiHarga
    {
        return $this->revisiHargas()->latest('tanggal_revisi')->first();
    }
}
