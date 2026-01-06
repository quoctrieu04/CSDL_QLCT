<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 18, 2); // ngân sách chi tiết
            $table->decimal('used_amount', 18, 2)->default(0); 
            $table->tinyInteger('month');     // 1..12
            $table->smallInteger('year');

            $table->timestamps();
            $table->unique(['budget_id','month','year']); // mỗi tháng 1 dòng cho 1 budget
        });
    }
    public function down(): void {
        Schema::dropIfExists('budget_details');
    }
};
