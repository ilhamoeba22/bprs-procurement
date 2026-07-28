<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegasi_approvals', function (Blueprint $table) {
            if (!Schema::hasColumn('delegasi_approvals', 'deactivated_by')) {
                $table->foreignId('deactivated_by')->nullable()->after('created_by')->constrained('users', 'id_user')->nullOnDelete();
            }
            if (!Schema::hasColumn('delegasi_approvals', 'deactivated_at')) {
                $table->dateTime('deactivated_at')->nullable()->after('deactivated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delegasi_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('delegasi_approvals', 'deactivated_by')) {
                $table->dropForeign(['deactivated_by']);
                $table->dropColumn('deactivated_by');
            }
            if (Schema::hasColumn('delegasi_approvals', 'deactivated_at')) {
                $table->dropColumn('deactivated_at');
            }
        });
    }
};
