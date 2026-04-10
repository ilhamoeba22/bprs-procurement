<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->date('tanggal_dp_aktual')->nullable()->after('tanggal_dp');
            $table->date('tanggal_pelunasan_aktual')->nullable()->after('tanggal_pelunasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->dropColumn('tanggal_dp_aktual');
            $table->dropColumn('tanggal_pelunasan_aktual');
        });
    }
};
