<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\User;

class ApprovalVerificationController extends Controller
{
    public function show(Pengajuan $pengajuan, User $user)
    {
        // Anda bisa menambahkan logika lain di sini jika perlu
        // Misalnya, mengambil timestamp approval

        return view('verification.show', [
            'pengajuan' => $pengajuan,
            'user' => $user,
        ]);
    }
}
