<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->unsignedBigInteger('disbursed_by')->nullable()->after('direktur_utama_approved_by');
            $table->foreign('disbursed_by')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropForeign(['disbursed_by']);
            $table->dropColumn('disbursed_by');
        });
    }
};
