<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->string('bukti_dp')->nullable()->after('tanggal_pelunasan');
            $table->string('bukti_pelunasan')->nullable()->after('bukti_dp');
        });
    }

    public function down(): void
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn(['bukti_dp', 'bukti_pelunasan']);
        });
    }
};
