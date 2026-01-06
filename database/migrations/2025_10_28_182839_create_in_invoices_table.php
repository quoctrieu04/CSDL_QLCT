<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('in_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incat_id')->constrained('in_categories')->cascadeOnDelete();
            $table->foreignId('banktrans_id')->constrained('bank_transactions')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('content')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'incat_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('in_invoices');
    }
};
