<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('nhac_nho', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('noi_dung');
      $table->date('ngay_nhac');
      $table->boolean('da_hoan_thanh')->default(false);
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('nhac_nho'); }
};
