<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();

            // Passport user
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('name');

            // bank | stock
            $table->enum('type', ['bank', 'stock']);

            // Common
            $table->decimal('buy_price', 18, 2);
            $table->decimal('current_price', 18, 2);
            $table->decimal('quantity', 18, 6)->default(1);

            // Bank only
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->date('start_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
