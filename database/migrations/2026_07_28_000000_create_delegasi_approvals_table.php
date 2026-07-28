<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegasi_approvals', function (Blueprint $table) {
            $table->id('id_delegasi');
            $table->foreignId('id_user_pemberi')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->foreignId('id_user_penerima')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->string('tipe_halangan')->default('Cuti Tahunan');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->text('alasan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegasi_approvals');
    }
};
