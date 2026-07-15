<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->foreignId('id_divisi')
                ->nullable()
                ->after('id_user_pemohon')
                ->constrained('divisis', 'id_divisi')
                ->nullOnDelete();
        });

        // Migrate existing pengajuans data by copying the division from user record
        DB::table('pengajuans')
            ->join('users', 'pengajuans.id_user_pemohon', '=', 'users.id_user')
            ->update(['pengajuans.id_divisi' => DB::raw('users.id_divisi')]);
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropForeign(['id_divisi']);
            $table->dropColumn('id_divisi');
        });
    }
};
