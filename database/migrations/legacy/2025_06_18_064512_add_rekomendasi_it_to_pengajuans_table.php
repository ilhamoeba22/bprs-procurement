<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('rekomendasi_it_tipe')->nullable()->after('catatan_revisi');
            $table->text('rekomendasi_it_catatan')->nullable()->after('rekomendasi_it_tipe');
        });
    }
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['rekomendasi_it_tipe', 'rekomendasi_it_catatan']);
        });
    }
};
