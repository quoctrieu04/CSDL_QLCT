<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('alerts', function (Blueprint $table) {
            // thêm cột message để lưu nguyên văn thông điệp từ AI
            $table->text('message')->nullable()->after('level');

            // (khuyến nghị) index phục vụ query list alerts
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['alerts_user_id_status_created_at_index']);
            $table->dropColumn('message');
        });
    }
};
