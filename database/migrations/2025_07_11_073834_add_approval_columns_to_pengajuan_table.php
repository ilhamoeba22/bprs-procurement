<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('direktur_utama_decision_type')->nullable()->after('direktur_utama_approved_by');
            $table->text('direktur_utama_catatan')->nullable()->after('direktur_utama_decision_type');
            $table->string('direktur_operasional_decision_type')->nullable()->after('direktur_operasional_approved_by');
            $table->text('direktur_operasional_catatan')->nullable()->after('direktur_operasional_decision_type');
        });
    }

    public function down()
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'direktur_utama_decision_type',
                'direktur_utama_catatan',
                'direktur_operasional_decision_type',
                'direktur_operasional_catatan',
            ]);
        });
    }
};
