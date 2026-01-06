<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('scope')->default('total');      // total | category:<id>
            $table->string('code');                          // OVER_BUDGET | NEAR_BUDGET | SPIKE_DAILY | PACE_RISK
            $table->string('level');                         // info | warning | critical
            $table->json('context');                         // payload chi tiết
            $table->string('hash')->unique();                // idempotency
            $table->string('status')->default('new');        // new|read|muted|resolved
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('alerts'); }
};
