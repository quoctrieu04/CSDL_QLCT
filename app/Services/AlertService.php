<?php

namespace App\Services;

use App\Models\Alert;
use Carbon\Carbon;

class AlertService
{
    public function forUserMonth(int $userId, ?int $year = null, ?int $month = null): array
    {
        if ($year && $month) {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end   = (clone $start)->endOfMonth()->endOfDay();
        } else {
            $end = now();
            $start = (clone $end)->subDays(30);
        }

        $rows = Alert::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['new', 'open'])   // << chỉ lấy cảnh báo còn hiệu lực
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $map = ['critical' => 'danger', 'warning' => 'warn', 'info' => 'info'];

        return $rows->map(function ($a) use ($map) {
            return [
                'id'         => $a->id,
                'code'       => $a->code,                          // OVER_BUDGET | ...
                'level'      => $map[$a->level] ?? 'info',         // danger|warn|info (khớp Flutter)
                'message'    => $this->message($a->code, $a->context ?? []),
                'context'    => $a->context,
                'created_at' => $a->created_at->toIso8601String(),
                'status'     => $a->status,
                'scope'      => $a->scope,
            ];
        })->values()->all();
    }

    private function message(string $code, array $ctx): string
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
