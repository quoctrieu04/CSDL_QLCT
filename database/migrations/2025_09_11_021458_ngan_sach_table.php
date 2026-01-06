<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ngan_sach', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('danh_muc_id')->nullable()->constrained('danh_muc')->nullOnDelete();
      $table->unsignedSmallInteger('nam');
      $table->unsignedTinyInteger('thang'); // 1..12
      $table->decimal('han_muc',14,2); // số tiền mục tiêu
      $table->timestamps();
      $table->unique(['user_id','danh_muc_id','nam','thang']);
    });
  }
  public function down(): void { Schema::dropIfExists('ngan_sach'); }
};

