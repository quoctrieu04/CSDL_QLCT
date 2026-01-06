<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('saving_transaction', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('saving_id');
            $table->unsignedBigInteger('bank_id')->nullable();

            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('note')->nullable();

            $table->timestamps();

            // Foreign key đúng
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')           // sửa bảng user => users
                  ->onDelete('cascade');

            $table->foreign('saving_id')
                  ->references('id')
                  ->on('saving')          // bảng saving của bạn
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_transaction');
    }
};
