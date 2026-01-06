<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_category_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            // Ràng buộc quan hệ
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('in_categories')->onDelete('cascade');

            // Mỗi user chỉ có 1 record cho mỗi (category, year, month)
            $table->unique(['user_id', 'category_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_category_balances');
    }
};
