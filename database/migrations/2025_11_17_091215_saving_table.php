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
    Schema::create('saving', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');

        $table->string('title');
        $table->decimal('target_amount', 15, 2)->default(0);
        $table->decimal('current_amount', 15, 2)->default(0);
        $table->decimal('monthly_amount', 15, 2)->default(0);

        $table->date('start_date');
        $table->enum('status', ['active', 'paused', 'finished'])->default('active');

        $table->timestamps();

        // Foreign key
       $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

    });
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
