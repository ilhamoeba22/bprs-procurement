<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsFinalToVendorPembayaran extends Migration
{
    public function up()
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('nominal_dp');
        });

        // Pastikan hanya satu vendor final per pengajuan
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->unique(['id_pengajuan', 'is_final'], 'vendor_pembayaran_unique_final');
        });
    }

    public function down()
    {
        Schema::table('vendor_pembayaran', function (Blueprint $table) {
            $table->dropUnique('vendor_pembayaran_unique_final');
            $table->dropColumn('is_final');
        });
    }
}
