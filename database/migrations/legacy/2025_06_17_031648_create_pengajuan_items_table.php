<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_items', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_pengajuan')->constrained('pengajuans', 'id_pengajuan')->onDelete('cascade');
            $table->string('kategori_barang');
            $table->string('nama_barang');
            $table->integer('kuantitas');
            $table->text('spesifikasi');
            $table->text('justifikasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_items');
    }
};
