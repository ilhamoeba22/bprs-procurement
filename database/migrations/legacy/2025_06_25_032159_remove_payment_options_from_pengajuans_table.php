<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ini akan menghapus kolom-kolom yang tidak lagi kita butuhkan.
     */
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Pastikan kolom ada sebelum mencoba menghapusnya untuk menghindari error
            if (Schema::hasColumns('pengajuans', ['opsi_pembayaran', 'tanggal_dp', 'tanggal_pelunasan'])) {
                $table->dropColumn(['opsi_pembayaran', 'tanggal_dp', 'tanggal_pelunasan']);
            }
        });
    }

    /**
     * Reverse the migrations.
     * Ini akan membuat kembali kolom-kolom tersebut jika kita perlu membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('opsi_pembayaran')->nullable();
            $table->date('tanggal_dp')->nullable();
            $table->date('tanggal_pelunasan')->nullable();
        });
    }
};
