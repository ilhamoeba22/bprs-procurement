<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldsToRevisiHargasTable extends Migration
{
    public function up()
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->string('revisi_direktur_operasional_decision_type')->nullable()->after('revisi_kadiv_ga_catatan');
            $table->text('revisi_direktur_operasional_catatan')->nullable()->after('revisi_direktur_operasional_decision_type');
            $table->unsignedBigInteger('revisi_direktur_operasional_approved_by')->nullable()->after('revisi_direktur_operasional_catatan');
            $table->string('revisi_direktur_utama_decision_type')->nullable()->after('revisi_direktur_operasional_approved_by');
            $table->text('revisi_direktur_utama_catatan')->nullable()->after('revisi_direktur_utama_decision_type');
            $table->unsignedBigInteger('revisi_direktur_utama_approved_by')->nullable()->after('revisi_direktur_utama_catatan');

            $table->foreign('revisi_direktur_operasional_approved_by')->references('id_user')->on('users')->onDelete('set null');
            $table->foreign('revisi_direktur_utama_approved_by')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropForeign(['revisi_direktur_operasional_approved_by']);
            $table->dropForeign(['revisi_direktur_utama_approved_by']);
            $table->dropColumn([
                'revisi_direktur_operasional_decision_type',
                'revisi_direktur_operasional_catatan',
                'revisi_direktur_operasional_approved_by',
                'revisi_direktur_utama_decision_type',
                'revisi_direktur_utama_catatan',
                'revisi_direktur_utama_approved_by',
            ]);
        });
    }
}
