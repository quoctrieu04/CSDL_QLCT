<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('investment_id');

            $table->decimal('amount', 18, 2);
            $table->smallInteger('operation')->comment('1 = in, -1 = out');
            $table->decimal('balance', 18, 2)->comment('số dư đầu tư sau giao dịch');

            $table->text('description')->nullable();

            $table->timestamps();

            // index
            $table->index(['user_id', 'investment_id']);

            // foreign key (khuyến nghị)
            $table->foreign('investment_id')
                ->references('id')
                ->on('investments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
