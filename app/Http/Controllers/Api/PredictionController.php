<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Alert;
use App\Services\LinearRegressionService;
use Carbon\Carbon;

class PredictionController extends Controller
{
    /**
     * Dự báo chi tiêu tháng kế tiếp cho user hiện tại
     * + trả kèm cảnh báo trong tháng (alerts).
     */
    public function predictMonthly(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 1️⃣ Xác định tháng/năm đang xem (mặc định: tháng hiện tại)
        $year  = (int)($request->query('year')  ?? now()->year);
        $month = (int)($request->query('month') ?? now()->month);

        // 2️⃣ Tổng thu/chi/số giao dịch trong tháng đang xem
        $txQuery = Transaction::where('user_id', $user->id)
            ->whereYear('occurred_at', $year)
            ->whereMonth('occurred_at', $month);

        $totalExpense = (float) (clone $txQuery)->where('type', 'chi')->sum('amount');
        $totalIncome  = (float) (clone $txQuery)->where('type', 'thu')->sum('amount');
        $txCount      = (float) (clone $txQuery)->count();

        // 3️⃣ Lấy đặc trưng từ view v_training (lag, moving average,…)
        $vt = DB::table('v_training')
            ->where('user_id', $user->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first() ?? (object)[];

        // 4️⃣ Tính tỷ lệ chi/thu
        $ratio = ($totalIncome > 0) ? ($totalExpense / $totalIncome) : 0.0;

        // 5️⃣ Chuẩn bị feature cho mô hình dự báo
        $features = [
            'total_expense'        => $totalExpense,
            'transaction_count'    => $txCount,
            'lag1_expense'         => (float)($vt->lag1_expense ?? 0),
            'lag2_expense'         => (float)($vt->lag2_expense ?? 0),
            'ma3_expense'          => (float)($vt->ma3_expense ?? 0),
            'total_income'         => $totalIncome,
            'total_budget_amount'  => (float)($vt->total_budget_amount ?? 0),
            'lag1_budget_amount'   => (float)($vt->lag1_budget_amount ?? 0),
            'expense_income_ratio' => $ratio,
        ];

        // 6️⃣ Dự báo bằng LinearRegressionService
        $yhat = app(LinearRegressionService::class)->predict($features);

        // 7️⃣ Fallback nếu model lỗi/NaN
        if (!is_finite($yhat) || $yhat <= 0) {
            $yhat = (float)($vt->ma3_expense ?? $vt->lag1_expense ?? 0);
        }

        // 8️⃣ Lấy cảnh báo trong tháng
        [$start, $end] = $this->monthBounds($year, $month);

        $rawAlerts = Alert::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        // 9️⃣ Map dữ liệu cảnh báo trả về cho app
        $alerts = $rawAlerts->map(function ($a) {
            $map = ['critical' => 'danger', 'warning' => 'warn', 'info' => 'info'];

            // ✅ Ưu tiên message từ DB, fallback nếu chưa có
            $msg = $a->message;
            if (!$msg || trim($msg) === '') {
                $msg = $this->buildMessage($a->code, $a->context ?? []);
            }

            return [
                'id'         => $a->id,
                'code'       => $a->code,
                'level'      => $map[$a->level] ?? 'info',
                'message'    => $msg,
                'context'    => $a->context,
                'scope'      => $a->scope,
                'status'     => $a->status,
                'created_at' => $a->created_at?->toIso8601String(),
            ];
        })->values()->all();

        // 🔟 Trả kết quả cuối cùng
        return response()->json([
            'user_id'    => $user->id,
            'year'       => $year,
            'month'      => $month,
            'prediction' => round((float)$yhat, 0),
            'features'   => $features,
            'alerts'     => $alerts,
        ]);
    }

    private function monthBounds(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();
        return [$start, $end];
    }

    /**
     * Dự phòng tạo message nếu DB chưa có message.
     */
    private function buildMessage(string $code, array $ctx): string
    {
        return match ($code) {
            'OVER_BUDGET' => sprintf(
                "Chi tiêu MTD đã vượt dự báo (%.0f/%.0f ~ %.0f%%).",
                (float)($ctx['mtd_expense'] ?? 0),
                (float)($ctx['pred_next_month'] ?? 0),
                100 * (float)($ctx['ratio'] ?? 0)
            ),
            'NEAR_BUDGET' => sprintf(
                "Sắp chạm ngưỡng dự báo (MTD đạt ~%.0f%% mục tiêu).",
                100 * (float)($ctx['ratio'] ?? 0)
            ),
            'SPIKE_DAILY' => sprintf(
                "Chi tiêu hôm qua tăng đột biến (%.0f so với TB7 ngày %.0f; >%.1fx).",
                (float)($ctx['yesterday_expense'] ?? 0),
                (float)($ctx['avg_prev_7d'] ?? 0),
                (float)($ctx['k'] ?? 2.0)
            ),
            'PACE_RISK' => sprintf(
                "Tốc độ chi hiện tại có nguy cơ vượt dự báo (≈ %.0f%%).",
                100 * (float)($ctx['pace_ratio'] ?? 0)
            ),
            default => 'Có cảnh báo mới.',
        };
    }
}
