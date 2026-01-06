<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('danh_mucs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('ten', 50);
    $table->timestamps();

    $table->unique(['user_id','ten']); // mỗi user không được trùng tên
});

    }

    public function down(): void
    {
        Schema::dropIfExists('danh_mucs');
    }
};
