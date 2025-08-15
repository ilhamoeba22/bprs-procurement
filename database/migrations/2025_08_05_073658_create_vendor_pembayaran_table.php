<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Creating table for vendor payment details
        Schema::create('vendor_pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_pengajuan')->constrained('pengajuans', 'id_pengajuan')->onDelete('cascade');
            $table->string('nama_vendor');
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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_pembayaran');
    }
};
