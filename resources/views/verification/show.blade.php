<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Persetujuan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Menambahkan font yang lebih baik */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-xl w-full bg-white p-8 rounded-2xl shadow-sm border border-gray-200">

        {{-- FIX: Menambahkan Logo --}}
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo_mci.png') }}" alt="Logo Perusahaan" class="mx-auto h-12">
        </div>

        <div class="text-center">
            <div class="inline-flex items-center bg-green-100 text-green-800 text-base font-semibold px-5 py-2 rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                TERVERIFIKASI
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Dokumen Valid</h1>
            <p class="text-gray-500 mt-2 leading-relaxed">
                Dokumen untuk pengajuan nomor <strong>{{ $pengajuan->kode_pengajuan }}</strong> telah disetujui secara sah oleh:
            </p>
        </div>

        <div class="mt-8 border-t border-gray-200 pt-6">
            <dl class="grid grid-cols-3 gap-x-4 gap-y-4">
                <dt class="font-medium text-gray-500 col-span-1">Nama</dt>
                <dd class="text-gray-800 font-semibold col-span-2">{{ $user->nama_user ?? 'N/A' }}</dd>

                <dt class="font-medium text-gray-500 col-span-1">NIK</dt>
                <dd class="text-gray-800 font-semibold col-span-2">{{ $user->nik_user ?? 'N/A' }}</dd>

                {{-- FIX: Mengambil nama jabatan dari data JSON --}}
                <dt class="font-medium text-gray-500 col-span-1">Jabatan</dt>
                <dd class="text-gray-800 font-semibold col-span-2">{{ (json_decode($user->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</dd>

                {{-- FIX: Mengubah waktu ke zona WIB --}}
                <dt class="font-medium text-gray-500 col-span-1">Waktu Persetujuan</dt>
                <dd class="text-gray-800 font-semibold col-span-2">{{ $pengajuan->updated_at->timezone('Asia/Jakarta')->translatedFormat('d F Y, \p\u\k\u\l H:i T') }}</dd>
            </dl>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ url('/admin/login') }}" class="inline-block bg-gray-800 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                Halaman Login
            </a>
        </div>
    </div>
</body>

</html>