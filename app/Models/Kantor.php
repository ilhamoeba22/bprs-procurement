<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kantor extends Model
{
    use HasFactory;

    protected $connection = 'sso';
    protected $primaryKey = 'id';
    protected $fillable = ['nama_kantor', 'alamat_kantor', 'kode_kantor'];
}
