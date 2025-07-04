<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->string('metode_pembayaran')->nullable()->after('bukti_path');
            $table->string('nama_rekening')->nullable()->after('metode_pembayaran');
            $table->string('no_rekening')->nullable()->after('nama_rekening');
            $table->string('nama_bank')->nullable()->after('no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn(['metode_pembayaran', 'nama_rekening', 'no_rekening', 'nama_bank']);
        });
    }
};
