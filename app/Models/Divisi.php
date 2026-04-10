<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Divisi extends Model
{
    use HasFactory;

    protected $connection = 'sso';
    protected $primaryKey = 'id';
    protected $fillable = ['nama_divisi', 'kantor_id'];

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kantor_id');
    }
}
