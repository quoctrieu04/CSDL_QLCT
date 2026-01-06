<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InCategoryBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InCategoryBalanceController extends Controller
{
    // 📅 Lấy danh sách balance theo tháng
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year');
        $month = $request->input('month');

        if (!$year || !$month) {
            return response()->json(['message' => 'Missing year or month'], 400);
        }

        $balances = DB::table('in_categories as c')
            ->leftJoin('in_category_balances as b', function ($join) use ($user, $year, $month) {
                $join->on('b.category_id', '=', 'c.id')
                     ->where('b.user_id', '=', $user->id)
                     ->where('b.year', '=', $year)
                     ->where('b.month', '=', $month);
            })
            ->where('c.user_id', $user->id)
            ->select(
                'c.id',
                'c.title',
                'c.currency',
                DB::raw('COALESCE(b.amount, 0) as balance')
            )
            ->orderBy('c.title')
            ->get();

        return response()->json($balances);
    }

    // ➕ Tạo hoặc cập nhật số tiền tháng
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => 'required|exists:in_categories,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
        ]);

        $data = [
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'amount' => $validated['amount'],
        ];

        $balance = InCategoryBalance::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            ['amount' => $validated['amount']]
        );

        return response()->json($balance, 201);
    }
}
