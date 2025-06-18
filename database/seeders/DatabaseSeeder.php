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
            ['kode_kantor' => '01'],
            ['nama_kantor' => 'Kantor Pusat', 'alamat_kantor' => 'Jl. Kaliurang No.KM 9, Gondangan, Sardonoharjo, Kec. Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55581']
        );

        $divisi = Divisi::firstOrCreate(
            ['nama_divisi' => 'IT, MIS, & Product Development', 'id_kantor' => $kantor->id_kantor]
        );

        $jabatan = Jabatan::firstOrCreate(
            ['nama_jabatan' => 'Super Administrator', 'id_kantor' => $kantor->id_kantor, 'id_divisi' => $divisi->id_divisi],
            ['type_jabatan' => 'Administrator']
        );

        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::firstOrCreate(
            ['nik_user' => 'superadmin'],
            [
                'nama_user' => 'Super Admin',
                'password' => Hash::make('password'),
                'id_kantor' => $kantor->id_kantor,
                'id_divisi' => $divisi->id_divisi,
                'id_jabatan' => $jabatan->id_jabatan,
            ]
        );

        // Beri peran Super Admin ke user tersebut
        $user->assignRole('Super Admin');
    }
}
