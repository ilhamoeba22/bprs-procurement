<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->string('opsi_pembayaran')->nullable()->after('bukti_path');
            $table->decimal('nominal_dp', 15, 2)->nullable()->after('opsi_pembayaran');
            $table->date('tanggal_dp')->nullable()->after('nominal_dp');
            $table->date('tanggal_pelunasan')->nullable()->after('tanggal_dp');
        });
    }

    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn(['opsi_pembayaran', 'nominal_dp', 'tanggal_dp', 'tanggal_pelunasan']);
        });
    }
};
