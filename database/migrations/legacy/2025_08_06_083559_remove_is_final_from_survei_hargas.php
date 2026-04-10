<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveIsFinalFromSurveiHargas extends Migration
{
    public function up()
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->dropColumn('is_final');
        });
    }

    public function down()
    {
        Schema::table('survei_hargas', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('nominal_pajak');
        });
    }
}
