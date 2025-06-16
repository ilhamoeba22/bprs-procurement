<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute; // <-- Tambahkan ini
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Tentukan primary key jika bukan 'id'.
     */
    protected $primaryKey = 'id_user';

    /**
     * Kolom yang boleh diisi, termasuk nama_user.
     */
    protected $fillable = [
        'nama_user',
        'nik_user',
        'password',
        'id_kantor',
        'id_divisi',
        'id_jabatan',
    ];

    /**
     * Kolom yang disembunyikan.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Izin akses ke panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Memberitahu Filament cara mendapatkan nama user untuk ditampilkan.
     */
    public function getFilamentName(): string
    {
        return $this->nama_user;
    }

    //==============================================================
    // AKSESOR (JEMBATAN) - KODE PERBAIKAN UTAMA
    //==============================================================
    /**
     * Membuat atribut 'name' virtual.
     * Ini akan membuat model kita kompatibel dengan bagian mana pun
     * dari framework yang mungkin masih mencari atribut 'name' secara default.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->nama_user,
        );
    }
    //==============================================================

    /**
     * Relasi ke Jabatan.
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }
}
