<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jabatan extends Model
{
    use HasFactory;

    protected $connection = 'sso';
    protected $primaryKey = 'id';
    protected $fillable = ['nama_jabatan', 'type_jabatan', 'kantor_id', 'divisi_id', 'acc_jabatan_id'];

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kantor_id');
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'acc_jabatan_id');
    }
}
