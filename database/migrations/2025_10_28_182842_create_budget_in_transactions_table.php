<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
         
        Schema::create('budget_in_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();

            // +1 tăng ngân sách, -1 giảm
            $table->smallInteger('operation'); 
            $table->decimal('amount', 18, 2);
            $table->decimal('prebalance', 18, 2)->nullable();

            // Nếu điều chỉnh ngân sách bắt nguồn từ 1 bank transaction nào đó
            $table->foreignId('banktransaction_id')->nullable()
                  ->constrained('bank_transactions')->nullOnDelete();

            $table->timestamps();
            $table->index(['user_id','budget_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('budget_in_transactions');
    }
};
