<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jabatan;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Jabatan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $idKantor = 1;

        $structure = [
            'Bisnis' => [
                'Account Officer UMKM',
                'Funding Officer Retail',
                'Manager Pembiayaan',
                'Account Officer Retail',
                'Team Leader Funding',
                'Funding Officer Corporate',
                'Kepala Divisi Bisnis',
                'Team Leader ZIS, WAF & Social Media'
            ],
            'Corporate Secretary' => [
                'Staff Admin Corporate Secretary',
                'Kepala Divisi Corporate Secretary',
                'Staf Design Grafis'
            ],
            'Pengurus' => [
                'Direktur Utama',
                'Direktur Operasional & Kepatuhan'
            ],
            'HR, GA dan Legal' => [
                'Staff Appraisal',
                'Office Boy',
                'Staff Admin Legal',
                'Security',
                'Driver',
                'Staff GA',
                'Staff HRD',
                'Manager Legal',
                'Kepala Divisi HR, GA dan Legal'
            ],
            'IT, MIS & Product Development' => [
                'Staff IT & MIS',
                'Team Leader IT & MIS',
                'Kadiv IT, MIS & Product Development',
                'System Administrator'
            ],
            'Manajemen Risiko' => ['Pejabat Eksekutif Manajemen Risiko'],
            'Operasional' => [
                'Staff Accounting',
                'Staff Operasional',
                'Teller',
                'Kepala Divisi Operasional',
                'Customer Service',
                'Head Teller & Customer Service'
            ],
            'Remedial & Collection' => [
                'Staff Asset Management Unit (AMU)',
                'Staff Collection & Remedial',
                'Team Leader Collection & Remedial'
            ],
            'Satuan Kerja Audit Internal (SKAI)' => [
                'Staff Satuan Kerja Audit Internal (SKAI)',
                'Kepala Satuan Kerja Audit Internal (SKAI)'
            ],
            'Satuan Kerja Kepatuhan & APU-PPT' => ['Kepala Satuan Kerja Kepatuhan & APU-PPT']
        ];

        // Tahap 1: Buat semua jabatan sesuai dengan divisinya
        foreach ($structure as $divisionName => $jabatans) {
            $divisi = Divisi::where('nama_divisi', $divisionName)->first();
            if ($divisi) {
                foreach ($jabatans as $jabatanName) {
                    Jabatan::create([
                        'nama_jabatan' => $jabatanName,
                        'id_divisi' => $divisi->id_divisi,
                        'id_kantor' => $idKantor,
                    ]);
                }
            }
        }

        // === HIERARKI SESUAI PDF BARU ===
        // Tahap 2: Tetapkan Atasan Langsung (acc_jabatan_id)
        // Struktur: 'Jabatan Bawahan' => 'Jabatan Atasan'
        $hierarchy = [
            'Staff Admin Corporate Secretary' => 'Kepala Divisi Corporate Secretary',
            'Kepala Divisi Corporate Secretary' => 'Direktur Utama',
            'Staf Design Grafis' => 'Kepala Divisi Corporate Secretary',
            'Account Officer UMKM' => 'Manager Pembiayaan',
            'Funding Officer Retail' => 'Team Leader Funding',
            'Manager Pembiayaan' => 'Kepala Divisi Bisnis',
            'Account Officer Retail' => 'Manager Pembiayaan',
            'Team Leader Funding' => 'Kepala Divisi Bisnis',
            'Funding Officer Corporate' => 'Team Leader Funding',
            'Kepala Divisi Bisnis' => 'Direktur Utama',
            'Team Leader ZIS, WAF & Social Media' => 'Kepala Divisi Bisnis',
            'Staff Appraisal' => 'Manager Legal',
            'Office Boy' => 'Staff GA',
            'Staff Admin Legal' => 'Manager Legal',
            'Security' => 'Staff GA',
            'Driver' => 'Staff GA',
            'Staff GA' => 'Kepala Divisi HR, GA dan Legal',
            'Staff HRD' => 'Kepala Divisi HR, GA dan Legal',
            'Manager Legal' => 'Kepala Divisi HR, GA dan Legal',
            'Kepala Divisi HR, GA dan Legal' => 'Direktur Operasional & Kepatuhan',
            'Staf IT & MIS' => 'Team Leader IT & MIS',
            'Team Leader IT & MIS' => 'Kadiv IT, MIS & Product Development',
            'Kadiv IT, MIS & Product Development' => 'Direktur Operasional & Kepatuhan',
            'Staff Accounting' => 'Kepala Divisi Operasional',
            'Staff Operasional' => 'Kepala Divisi Operasional',
            'Teller' => 'Head Teller & Customer Service',
            'Kepala Divisi Operasional' => 'Direktur Operasional & Kepatuhan',
            'Customer Service' => 'Head Teller & Customer Service',
            'Head Teller & Customer Service' => 'Kepala Divisi Operasional',
            'Staf Asset Mangement Unit (AMU)' => 'Kepala Divisi Collection & Remedial',
            'Staff Collection & Remedial' => 'Team Leader Collection & Remedial',
            'Kepala Divisi Collection & Remedial' => 'Direktur Utama',
            'Team Leader Collection & Remedial' => 'Kepala Divisi Collection & Remedial',
            'Staff Satuan Kerja Audit Internal (SKAI)' => 'Kepala Satuan Kerja Audit Internal (SKAI)',
            'Kepala Satuan Kerja Audit Internal (SKAI)' => 'Direktur Utama',
            'Pejabat Eksekutif Manajemen Risiko' => 'Direktur Operasional & Kepatuhan',
            'Kepala Satuan Kerja Kepatuhan & APU-PPT' => 'Direktur Operasional & Kepatuhan',
        ];

        foreach ($hierarchy as $bawahanName => $atasanName) {
            // Mengganti nama jabatan yang mungkin berbeda antara PDF dan struktur awal
            if ($bawahanName === 'Staf IT & MIS') $bawahanName = 'Staff IT & MIS';
            if ($bawahanName === 'Staf Asset Mangement Unit (AMU)') $bawahanName = 'Staff Asset Management Unit (AMU)';

            $bawahan = Jabatan::where('nama_jabatan', $bawahanName)->first();
            $atasan = Jabatan::where('nama_jabatan', $atasanName)->first();

            if ($bawahan && $atasan) {
                $bawahan->acc_jabatan_id = $atasan->id_jabatan;
                $bawahan->save();
            }
        }
    }
}
