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
    Schema::create('investments', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('user_id');

        $table->string('name');                // Tên khoản đầu tư (Vàng, ETF, BTC...)
        $table->decimal('buy_price', 20, 2);   // Giá mua 1 đơn vị
        $table->decimal('current_price', 20, 2)->default(0); // Giá hiện tại 1 đơn vị
        $table->decimal('quantity', 20, 6);    // Số lượng mua (2 cây vàng, 10 chỉ = 3.73 lượng...)

        $table->decimal('total_invested', 20, 2); // buy_price * quantity
        $table->decimal('profit_loss', 20, 2)->default(0); // current_price * quantity - total_invested

        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
