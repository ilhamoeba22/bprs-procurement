<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PengajuanItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_item';
    protected $fillable = [
        'id_pengajuan',
        'kategori_barang',
        'nama_barang',
        'kuantitas',
        'spesifikasi',
        'justifikasi',
        'harga_final',
    ];

    /**
     * Get the related Pengajuan.
     */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    /**
     * Get the survey prices associated with this item.
     */
    public function surveiHargas(): HasMany
    {
        return $this->hasMany(SurveiHarga::class, 'id_item');
    }

    /**
     * Get the survey prices for procurement (Pengadaan).
     */
    public function surveiPengadaan(): HasMany
    {
        return $this->surveiHargas()->where('tipe_survei', 'Pengadaan');
    }

    /**
     * Get the survey prices for repair (Perbaikan).
     */
    public function surveiPerbaikan(): HasMany
    {
        return $this->surveiHargas()->where('tipe_survei', 'Perbaikan');
    }


    /**
     * Get all revisions for the item through survey prices.
     */
    // v-- 2. TAMBAHKAN METHOD BARU INI --v
    public function semuaRevisiHarga(): HasManyThrough
    {
        return $this->hasManyThrough(
            RevisiHarga::class,
            SurveiHarga::class,
            'id_item',
            'survei_harga_id',
            'id_item',
            'id'
        );
    }
}
