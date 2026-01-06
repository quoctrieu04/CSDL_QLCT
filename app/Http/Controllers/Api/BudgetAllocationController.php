<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Budget;
use App\Models\BudgetDetail;
use App\Models\BudgetInTransaction;

class BudgetAllocationController extends Controller
{
    /**
     * 📤 Phân bổ tiền vào ngân sách
     */
    public function allocate(Request $request)
    {
        \Log::info('🔥 allocate() called', ['body' => $request->all()]);

        $data = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.category_id' => 'required|integer|exists:out_categories,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        $userId = $request->user()->id ?? 1;
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        DB::beginTransaction();
        try {
            foreach ($data['allocations'] as $item) {
                $catId  = $item['category_id'];
                $amount = (float) $item['amount'];

                // 1️⃣ Tìm hoặc tạo ngân sách
                $budget = Budget::firstOrCreate(
                    [
                        'user_id'     => $userId,
                        'category_id' => $catId,
                    ],
                    [
                        'title'  => 'Tự động ' . $catId,
                        'amount' => 0,
                    ]
                );

                // 2️⃣ Cập nhật chi tiết tháng
                $detail = BudgetDetail::firstOrNew([
                    'budget_id' => $budget->id,
                    'user_id'   => $userId,
                    'month'     => $month,
                    'year'      => $year,
                ]);
                $detail->amount = ($detail->amount ?? 0) + $amount;
                $detail->save();

                // 3️⃣ Ghi log phân bổ
                BudgetInTransaction::create([
                    'user_id'   => $userId,
                    'budget_id' => $budget->id,
                    'operation' => 1, // ✅ Phân bổ
                    'amount'    => $amount,
                    'prebalance'=> $budget->amount,
                    'month'     => $month,
                    'year'      => $year,
                ]);

                // 4️⃣ Cập nhật tổng ngân sách
                $budget->amount += $amount;
                $budget->save();
            }

            DB::commit();
            return response()->json(['message' => '✅ Phân bổ thành công'], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('💥 Allocation failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📊 Tổng hợp ngân sách theo tháng
     */
   public function summary(Request $request)
{
    $userId = $request->user()->id ?? 1;
    $month = (int) $request->input('month', now()->month);
    $year  = (int) $request->input('year', now()->year);

    // 1️⃣ Lấy ngân sách tháng
    $budgets = DB::table('budgets as b')
        ->leftJoin('budget_details as bd', function ($join) use ($month, $year) {
            $join->on('bd.budget_id', '=', 'b.id')
                 ->where('bd.month', '=', $month)
                 ->where('bd.year', '=', $year);
        })
        ->where('b.user_id', $userId)
        ->select(
            'b.id',
            'b.category_id',
            'b.title as name',
            DB::raw('COALESCE(bd.amount, 0) as amount'),
            DB::raw("$month as month"),
            DB::raw("$year as year")
        )
        ->get();

    // 2️⃣ Lấy chi tiêu thực tế từ bảng out_invoices (lọc theo month, year)
    $spentByCat = DB::table('out_invoices')
        ->select('outcat_id as category_id', DB::raw('SUM(amount) as spent'))
        ->where('user_id', $userId)
        ->where('month', $month) // ✅ lọc đúng tháng
        ->where('year', $year)   // ✅ lọc đúng năm
        ->groupBy('outcat_id')
        ->pluck('spent', 'category_id');

    // 3️⃣ Gộp và trả kết quả
    $merged = $budgets->map(function ($b) use ($spentByCat) {
        $b->spent = $spentByCat[$b->category_id] ?? 0;
        return $b;
    });

    return response()->json([
        'data' => $merged,
        'total_assigned' => $merged->sum('amount'),
        'total_spent' => $merged->sum('spent'),
        'unallocated' => max($merged->sum('amount') - $merged->sum('spent'), 0),
    ]);
}



}
