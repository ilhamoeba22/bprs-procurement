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
        Schema::create('revisi_hargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_harga_id')->constrained('survei_hargas')->onDelete('cascade');
            $table->decimal('harga_revisi', 15, 2)->nullable();
            $table->enum('opsi_pajak', ['Pajak Sama', 'Pajak Berbeda'])->default('Pajak Sama');
            $table->enum('kondisi_pajak', ['Tidak Ada Pajak', 'Pajak ditanggung kita', 'Pajak ditanggung Vendor'])->nullable();
            $table->enum('jenis_pajak', ['PPh 21', 'PPh 23'])->nullable();
            $table->string('npwp_nik')->nullable();
            $table->string('nama_pemilik_pajak')->nullable();
            $table->decimal('nominal_pajak', 15, 2)->nullable();
            $table->text('alasan_revisi');
            $table->string('bukti_revisi')->nullable();
            $table->timestamp('tanggal_revisi');
            $table->foreignId('direvisi_oleh')->constrained('users', 'id_user'); // Referensi ke users.id_user
            $table->timestamps();
        });

        // Hapus kolom revisi dari survei_hargas jika ada
        Schema::table('survei_hargas', function (Blueprint $table) {
            $columns = ['harga_revisi', 'alasan_revisi', 'tanggal_revisi', 'bukti_revisi'];
            $existingColumns = array_intersect($columns, Schema::getColumnListing('survei_hargas'));
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tambahkan kembali kolom revisi ke survei_hargas
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->decimal('harga_revisi', 15, 2)->nullable();
            $table->string('bukti_revisi')->nullable();
            $table->text('alasan_revisi')->nullable();
            $table->timestamp('tanggal_revisi')->nullable();
        });

        Schema::dropIfExists('revisi_hargas');
    }
};
