<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')  // id từ bảng danh_mucs
                  ->constrained('danh_mucs')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamps();

            $table->unique(['user_id','category_id','year','month'], 'uniq_user_cat_ym');
            $table->index(['user_id','year','month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_months');
    }
};
