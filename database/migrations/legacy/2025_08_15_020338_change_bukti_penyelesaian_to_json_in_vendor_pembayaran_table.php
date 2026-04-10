<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            // Ubah tipe kolom menjadi JSON untuk menampung array file
            $table->json('bukti_penyelesaian')->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            // Kembalikan ke string jika di-rollback
            $table->string('bukti_penyelesaian')->nullable()->change();
        });
    }
};
