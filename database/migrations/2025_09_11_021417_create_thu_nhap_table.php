<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('thu_nhap', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('nguon')->nullable(); // lương, thưởng,...
      $table->decimal('so_tien',14,2);
      $table->date('ngay_thu')->index();
      $table->string('ghi_chu')->nullable();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('thu_nhap'); }
};
