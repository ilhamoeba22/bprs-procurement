<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jabatan extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_jabatan';
    protected $fillable = ['nama_jabatan', 'type_jabatan', 'id_kantor', 'id_divisi', 'acc_jabatan_id'];

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor', 'id_kantor');
    }
    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'id_divisi', 'id_divisi');
    }
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'acc_jabatan_id');
    }
}
