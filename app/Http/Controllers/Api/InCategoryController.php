<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InCategoryController extends Controller
{
    /**
     * 📅 Danh sách danh mục thu (lọc theo tháng/năm nếu có)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DB::table('in_categories')
            ->where('user_id', $user->id)
            ->select('id', 'title', 'balance', 'currency', 'month', 'year')
            ->orderBy('created_at', 'desc');

        // ✅ Nếu có query year/month thì lọc
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $items = $query->get();

        return response()->json([
            'data' => $items,
            'message' => 'Danh sách danh mục thu',
        ]);
    }

    /**
     * ➕ Tạo danh mục thu mới
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $id = DB::table('in_categories')->insertGetId([
            'user_id' => $user->id,
            'title' => $data['title'],
            'balance' => 0,
            'currency' => $data['currency'] ?? 'VND',
            'month' => $data['month'],     // ✅ lưu tháng
            'year' => $data['year'],       // ✅ lưu năm
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newItem = DB::table('in_categories')->find($id);

        return response()->json([
            'data' => $newItem,
            'message' => 'Đã tạo danh mục thu mới',
        ], 201);
    }

    /**
     * ✏️ Cập nhật danh mục thu
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10',
        ]);

        DB::table('in_categories')
            ->where('id', $id)
            ->update([
                'title' => $data['title'],
                'currency' => $data['currency'] ?? 'VND',
                'updated_at' => now(),
            ]);

        $updated = DB::table('in_categories')->find($id);

        return response()->json([
            'data' => $updated,
            'message' => 'Đã cập nhật danh mục thu',
        ]);
    }

    /**
     * ❌ Xóa danh mục thu
     */
    public function destroy($id)
    {
        DB::table('in_categories')->where('id', $id)->delete();
        return response()->json(['message' => 'Đã xóa danh mục thu']);
    }
}
