<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('opsi_pembayaran')->nullable()->after('kadiv_ga_catatan');
            $table->date('tanggal_dp')->nullable()->after('opsi_pembayaran');
            $table->date('tanggal_pelunasan')->nullable()->after('tanggal_dp');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['opsi_pembayaran', 'tanggal_dp', 'tanggal_pelunasan']);
        });
    }
};
