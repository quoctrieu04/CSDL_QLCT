<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1..12
            $table->decimal('limit', 16, 2)->default(0); // số tiền phân bổ
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'year', 'month'], 'uniq_user_cat_ym');
            $table->index(['user_id','year','month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
