<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // đảm bảo kiểu trùng với bảng gốc (unsignedBigInteger)
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('category_id')->change();

            // thêm FK (đặt đúng tên bảng danh mục của bạn: 'danh_mucs' hoặc 'categories')
            $table->foreign('user_id', 'fk_budgets_user')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('category_id', 'fk_budgets_category')
                  ->references('id')->on('danh_mucs') // <-- đổi nếu bảng bạn là 'categories'
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign('fk_budgets_user');
            $table->dropForeign('fk_budgets_category');
        });
    }
};
