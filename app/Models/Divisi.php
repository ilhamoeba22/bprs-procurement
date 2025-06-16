<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Divisi extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_divisi';
    protected $fillable = ['nama_divisi', 'id_kantor'];

    /**
     * Mendefinisikan relasi bahwa satu Divisi dimiliki oleh satu Kantor.
     * INI ADALAH FUNGSI YANG MEMPERBAIKI ERROR ANDA.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor', 'id_kantor');
    }
}
