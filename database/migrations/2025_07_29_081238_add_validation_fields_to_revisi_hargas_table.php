<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidationFieldsToRevisiHargasTable extends Migration
{
    public function up()
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->foreignId('revisi_budget_validated_by')->nullable()->constrained('users', 'id_user')->onDelete('set null');
            $table->timestamp('revisi_budget_validated_at')->nullable();
            $table->text('catatan_validasi')->nullable();
        });
    }

    public function down()
    {
        Schema::table('revisi_hargas', function (Blueprint $table) {
            $table->dropForeign(['revisi_budget_validated_by']);
            $table->dropColumn(['revisi_budget_validated_by', 'revisi_budget_validated_at', 'catatan_validasi']);
        });
    }
}
