<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            // Hapus kolom boolean 'kena_pajak' jika ada dari implementasi sebelumnya
            if (Schema::hasColumn('survei_hargas', 'kena_pajak')) {
                $table->dropColumn('kena_pajak');
            }

            // Tambahkan kolom baru untuk 3 kondisi pajak
            $table->string('kondisi_pajak')->default('Tidak Ada Pajak')->after('harga');

            // Pastikan kolom detail pajak lainnya sudah ada
            // Jika belum, Anda bisa menambahkannya di sini
            if (!Schema::hasColumn('survei_hargas', 'jenis_pajak')) {
                $table->string('jenis_pajak')->nullable()->after('kondisi_pajak');
                $table->string('npwp_nik')->nullable()->after('jenis_pajak');
                $table->string('nama_pemilik_pajak')->nullable()->after('npwp_nik');
                $table->decimal('nominal_pajak', 15, 2)->nullable()->after('nama_pemilik_pajak');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            //
        });
    }
};
