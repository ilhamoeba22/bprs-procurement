<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kantor;
use App\Models\Divisi;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $kantor = Kantor::firstOrCreate(
            ['kode_kantor' => 'KP01'],
            ['nama_kantor' => 'Kantor Pusat', 'alamat_kantor' => 'Jl. Jend. Sudirman No.1']
        );

        $divisi = Divisi::firstOrCreate(
            ['nama_divisi' => 'IT & MIS', 'id_kantor' => $kantor->id_kantor]
        );

        $jabatan = Jabatan::firstOrCreate(
            ['nama_jabatan' => 'Super Administrator', 'id_kantor' => $kantor->id_kantor, 'id_divisi' => $divisi->id_divisi],
            ['type_jabatan' => 'Administrator']
        );

        User::firstOrCreate(
            ['nik_user' => 'superadmin'],
            [
                'nama_user' => 'Super Admin',
                'password' => Hash::make('password'),
                'id_kantor' => $kantor->id_kantor,
                'id_divisi' => $divisi->id_divisi,
                'id_jabatan' => $jabatan->id_jabatan,
            ]
        );
    }
}
