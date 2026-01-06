<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('danh_mucs')->nullOnDelete();

            // dùng đúng enum bạn đang xài
            $table->enum('type', ['thu','chi']);
            $table->decimal('amount', 18, 2); // sẽ thêm CHECK > 0 phía dưới
            $table->string('note')->nullable();

            // dùng DATETIME để tránh rắc rối timezone của TIMESTAMP
            $table->dateTime('occurred_at');

            $table->timestamps();

            // indexes
            $table->index(['user_id','occurred_at'], 'idx_tx_user_time');
            $table->index(['user_id','category_id','occurred_at'], 'idx_tx_user_cat_time');
            $table->index(['user_id','wallet_id'], 'idx_tx_user_wallet');
            $table->index(['wallet_id','occurred_at'], 'idx_tx_wallet_time');
        });

        // MySQL 8+: ràng buộc số tiền > 0
        DB::statement("ALTER TABLE transactions
           ADD CONSTRAINT chk_transactions_amount_positive CHECK (amount > 0)");
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};
