<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('transactions', function (Blueprint $table) {
      $table->dropForeign(['wallet_id']);
    });
    Schema::table('transactions', function (Blueprint $table) {
      $table->foreignId('wallet_id')->nullable()->change();
    });
    Schema::table('transactions', function (Blueprint $table) {
      $table->foreign('wallet_id')
        ->references('id')->on('wallets')
        ->nullOnDelete()
        ->cascadeOnUpdate();
    });
  }

  public function down(): void
  {
    Schema::table('transactions', function (Blueprint $table) {
      $table->dropForeign(['wallet_id']);
    });
    Schema::table('transactions', function (Blueprint $table) {
      $table->foreignId('wallet_id')->nullable(false)->change();
    });
    Schema::table('transactions', function (Blueprint $table) {
      $table->foreign('wallet_id')
        ->references('id')->on('wallets')
        ->restrictOnDelete()
        ->cascadeOnUpdate();
    });
  }
};
