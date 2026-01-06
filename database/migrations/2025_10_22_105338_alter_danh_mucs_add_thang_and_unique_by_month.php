<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1️⃣ Thêm cột thang nếu chưa có
        if (!Schema::hasColumn('danh_mucs', 'thang')) {
            Schema::table('danh_mucs', function (Blueprint $table) {
                $table->date('thang')->nullable()->after('ten');
            });
        }

        // 2️⃣ Backfill thang = đầu tháng của created_at (chỉ cho bản ghi chưa có)
        DB::statement("UPDATE danh_mucs SET thang = DATE_FORMAT(created_at, '%Y-%m-01') WHERE thang IS NULL");

        // 3️⃣ Đảm bảo có index đơn cho user_id (để MySQL không phụ thuộc unique cũ)
        $hasUserIdIndex = (bool) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'danh_mucs')
            ->where('COLUMN_NAME', 'user_id')
            ->where('INDEX_NAME', 'danh_mucs_user_id_index')
            ->exists();

        if (!$hasUserIdIndex) {
            Schema::table('danh_mucs', function (Blueprint $table) {
                $table->index('user_id', 'danh_mucs_user_id_index');
            });
        }

        // 4️⃣ Tìm và xoá UNIQUE cũ nếu là (user_id, loai, ten) hoặc (user_id, ten)
        $oldIndexName = null;
        $indexes = DB::table('information_schema.STATISTICS')
            ->select('INDEX_NAME', DB::raw('GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as cols'))
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'danh_mucs')
            ->where('NON_UNIQUE', 0)
            ->groupBy('INDEX_NAME')
            ->get();

        foreach ($indexes as $idx) {
            if (in_array($idx->cols, ['user_id,loai,ten', 'user_id,ten'])) {
                $oldIndexName = $idx->INDEX_NAME;
                break;
            }
        }

        if ($oldIndexName) {
            try {
                Schema::table('danh_mucs', function (Blueprint $table) use ($oldIndexName) {
                    $table->dropUnique($oldIndexName);
                });
            } catch (\Throwable $e) {
                info("Không thể xoá index {$oldIndexName}: " . $e->getMessage());
            }
        }

        // 5️⃣ Kiểm tra trùng dữ liệu (user_id, ten, thang)
        $dup = DB::selectOne("
            SELECT 1 FROM (
                SELECT user_id, ten, thang, COUNT(*) c
                FROM danh_mucs
                GROUP BY user_id, ten, thang
                HAVING c > 1
            ) t LIMIT 1
        ");
        if ($dup) {
            throw new \RuntimeException('⚠️ Có bản ghi trùng (user_id, ten, thang). Hãy xoá hoặc sửa trùng trước khi migrate.');
        }

        // 6️⃣ Thêm UNIQUE mới nếu chưa có
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'danh_mucs')
            ->where('INDEX_NAME', 'danh_mucs_user_ten_thang_unique')
            ->exists();

        if (!$exists) {
            Schema::table('danh_mucs', function (Blueprint $table) {
                $table->unique(['user_id', 'ten', 'thang'], 'danh_mucs_user_ten_thang_unique');
            });
        }

        // 7️⃣ Đặt thang NOT NULL
        DB::statement("ALTER TABLE danh_mucs MODIFY thang DATE NOT NULL");
    }

    public function down(): void
    {
        Schema::table('danh_mucs', function (Blueprint $table) {
            $table->dropUnique('danh_mucs_user_ten_thang_unique');
        });

        if (Schema::hasColumn('danh_mucs', 'thang')) {
            Schema::table('danh_mucs', function (Blueprint $table) {
                $table->dropColumn('thang');
            });
        }
    }
};
