<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'tanggal_pelunasan'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PengajuanItem::class, 'id_item');
    }
}
