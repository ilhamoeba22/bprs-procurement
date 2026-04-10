<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Removing payment-related columns from survei_hargas
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn([
                'metode_pembayaran',
                'opsi_pembayaran',
                'nominal_dp',
                'tanggal_dp',
                'tanggal_pelunasan',
                'nama_rekening',
                'no_rekening',
                'nama_bank',
                'bukti_dp',
                'bukti_pelunasan',
                'bukti_penyelesaian',
            ]);
        });
    }

    public function down(): void
    {
        // Restoring payment-related columns in case of rollback
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->string('metode_pembayaran')->nullable();
            $table->string('opsi_pembayaran')->nullable();
            $table->decimal('nominal_dp', 15, 2)->nullable()->default(0);
            $table->date('tanggal_dp')->nullable();
            $table->date('tanggal_pelunasan')->nullable();
            $table->string('nama_rekening')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('bukti_dp')->nullable();
            $table->string('bukti_pelunasan')->nullable();
            $table->string('bukti_penyelesaian')->nullable();
        });
    }
};
