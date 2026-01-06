<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Doc/phiếu đi kèm (in/out invoice). Để nullable để tránh vòng FK.
            $table->unsignedBigInteger('doc_id')->nullable()->index();
            $table->string('doc_type', 10)->nullable(); // 'IN' hoặc 'OUT'

            $table->foreignId('bank_id')->constrained('bankaccounts')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('prebalance', 18, 2)->default(0);

            // +1 = thu (in), -1 = chi (out)
            $table->smallInteger('operation'); 

            $table->timestamps();

            $table->index(['user_id', 'bank_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('bank_transactions');
    }
};
