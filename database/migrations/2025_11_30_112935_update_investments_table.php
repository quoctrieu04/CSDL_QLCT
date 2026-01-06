<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('investments', function (Blueprint $table) {

            // Thêm cột type nếu chưa có
            if (!Schema::hasColumn('investments', 'type')) {
                $table->string('type')->default('custom');
            }

            // Thêm auto_update
            if (!Schema::hasColumn('investments', 'auto_update')) {
                $table->boolean('auto_update')->default(false);
            }

            // Thêm symbol
            if (!Schema::hasColumn('investments', 'symbol')) {
                $table->string('symbol')->nullable();
            }

            // Thêm api_source
            if (!Schema::hasColumn('investments', 'api_source')) {
                $table->string('api_source')->nullable();
            }

            // Thêm api_field
            if (!Schema::hasColumn('investments', 'api_field')) {
                $table->string('api_field')->nullable();
            }

            // Thêm api_path
            if (!Schema::hasColumn('investments', 'api_path')) {
                $table->string('api_path')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'auto_update',
                'symbol',
                'api_source',
                'api_field',
                'api_path',
            ]);
        });
    }
};
