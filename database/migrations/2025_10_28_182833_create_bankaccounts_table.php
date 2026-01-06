<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bankaccounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('banknumber', 60)->nullable();
            $table->string('bankname', 120)->nullable();
            $table->decimal('initamount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('currency', 10)->default('VND');
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamps();
            $table->softDeletes(); // nếu muốn xóa mềm
        });
    }
    public function down(): void {
        Schema::dropIfExists('bankaccounts');
    }
};
