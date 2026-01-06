<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chi_tieu', function (Blueprint $t) {
            $t->foreignId('danh_muc_id')
              ->nullable()
              ->after('ghi_chu')
              ->constrained('danh_muc')
              ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chi_tieu', function (Blueprint $t) {
            $t->dropForeign(['danh_muc_id']);
            $t->dropColumn('danh_muc_id');
        });
    }
};
