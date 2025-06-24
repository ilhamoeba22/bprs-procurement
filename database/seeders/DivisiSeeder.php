<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Divisi;
use App\Models\Kantor;

class DivisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pertama, pastikan kantor dengan id=1 sudah ada.
        // Jika belum ada, buat kantor default agar tidak terjadi error relasi.
        $kantor = Kantor::firstOrCreate(
            ['id_kantor' => 1],
            [
                'nama_kantor' => 'Kantor Pusat',
                'alamat_kantor' => 'Alamat Kantor Pusat Default',
                'kode_kantor' => '01'
            ]
        );

        // Daftar nama-nama divisi yang akan dibuat
        $divisions = [
            'Bisnis',
            'Corporate Secretary',
            'Pengurus',
            'HR, GA dan Legal',
            'IT, MIS & Product Development',
            'Manajemen Risiko',
            'Operasional',
            'Remedial & Collection',
            'Satuan Kerja Audit Internal (SKAI)',
            'Satuan Kerja Kepatuhan & APU-PPT'
        ];

        // Loop untuk membuat setiap divisi
        foreach ($divisions as $divisionName) {
            Divisi::firstOrCreate(
                [
                    'nama_divisi' => $divisionName,
                    'id_kantor' => $kantor->id_kantor
                ]
            );
        }
    }
}
