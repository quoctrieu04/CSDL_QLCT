<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('in_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->decimal('balance', 18, 2)->default(0); // tùy bạn có dùng không
            $table->string('currency', 10)->default('VND');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('in_categories');
    }
};
