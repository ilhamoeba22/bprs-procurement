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
        'opsi_pembayaran',
        'nominal_dp',
        'tanggal_dp',
        'tanggal_pelunasan',
        'metode_pembayaran',
        'nama_rekening',
        'no_rekening',
        'nama_bank',
        'bukti_dp',
        'bukti_pelunasan',
        'bukti_penyelesaian',
        'is_final',
        'kondisi_pajak',
        'jenis_pajak',
        'npwp_nik',
        'nama_pemilik_pajak',
        'nominal_pajak',
        'rincian_harga',
    ];

    /**
     * Mendapatkan PengajuanItem terkait.
     */
    public function pengajuanItem(): BelongsTo
    {
        return $this->belongsTo(PengajuanItem::class, 'id_item');
    }

    /**
     * Mendapatkan revisi harga untuk survei ini.
     */
    public function revisiHargas(): HasMany
    {
        return $this->hasMany(RevisiHarga::class, 'survei_harga_id');
    }

    /**
     * Mendapatkan revisi harga terbaru.
     */
    public function latestRevisi(): ?RevisiHarga
    {
        return $this->revisiHargas()->latest('tanggal_revisi')->first();
    }

    /**
     * Memeriksa apakah harga survei dapat direvisi.
     */
    public function canBeRevised(): bool
    {
        return !$this->is_final &&
            $this->tipe_survei === 'Pengadaan' &&
            $this->pengajuanItem &&
            $this->pengajuanItem->pengajuan &&
            $this->pengajuanItem->pengajuan->canRevisePrice();
    }
}
