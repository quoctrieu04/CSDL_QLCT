<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('out_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outcat_id')->constrained('out_categories')->cascadeOnDelete();
            $table->foreignId('banktrans_id')->constrained('bank_transactions')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('doc_type', 20)->nullable();   // nếu bạn cần phân loại thêm
            $table->unsignedBigInteger('doctrans_id')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'outcat_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('out_invoices');
    }
};
