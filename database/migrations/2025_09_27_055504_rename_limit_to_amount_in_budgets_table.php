<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Đổi tên cột
            $table->renameColumn('limit', 'amount');
        });

        Schema::table('budgets', function (Blueprint $table) {
            // Đổi kiểu dữ liệu: từ decimal(16,2) sang unsignedBigInteger
            // Lưu ý: nếu đã có dữ liệu, bạn nên backup trước!
            $table->unsignedBigInteger('amount')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->decimal('amount', 16, 2)->default(0)->change();
            $table->renameColumn('amount', 'limit');
        });
    }
};
