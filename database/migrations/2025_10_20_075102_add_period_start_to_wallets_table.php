<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Thêm cột period_start (ngày đầu tháng)
            $table->date('period_start')->nullable()->after('currency');
        });

        // Gán dữ liệu cũ: dùng created_at làm kỳ tháng
        DB::statement("
            UPDATE wallets
            SET period_start = DATE_FORMAT(created_at, '%Y-%m-01')
            WHERE period_start IS NULL
        ");

        // Tạo index và unique constraint để đảm bảo duy nhất theo user + tháng
        Schema::table('wallets', function (Blueprint $table) {
            $table->index(['user_id', 'period_start'], 'idx_wallets_user_period');
            $table->unique(['user_id', 'name', 'period_start'], 'uq_wallets_user_name_period');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('uq_wallets_user_name_period');
            $table->dropIndex('idx_wallets_user_period');
            $table->dropColumn('period_start');
        });
    }
};
