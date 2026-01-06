<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alert;
use Carbon\Carbon;

class AlertListController extends Controller
{
    public function index(Request $req)
    {
        $userId = $req->user()->id ?? $req->integer('user_id');
        abort_if(empty($userId), 400, 'user_id required');

        $status = $req->get('status', 'open');
        $limit  = min(max((int)$req->get('limit', 20), 1), 100);

        $year  = (int)($req->get('year',  Carbon::now()->year));
        $month = (int)($req->get('month', Carbon::now()->month));

        $alerts = Alert::where('user_id', $userId)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $items = $alerts->map(function (Alert $a) {
            return [
                'id'         => $a->id,
                'code'       => $a->code,
                'level'      => $a->level,
                'message'    => $a->message ?: $this->buildMessageFallback($a->code, $a->context),
                'context'    => $a->context,
                'status'     => $a->status,
                'created_at' => $a->created_at?->toISOString(),
            ];
        });

        return response()->json(['alerts' => $items], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function buildMessageFallback(string $code, ?array $ctx): string
    {
        $ctx = $ctx ?? [];
        if ($code === 'OVER_BUDGET') {
            $pct = isset($ctx['exceed_pct']) ? round((float)$ctx['exceed_pct'])
                : (isset($ctx['ratio']) ? max(0, ($ctx['ratio'] - 1) * 100) : 0);
            $amt = isset($ctx['exceed_amt']) ? (float)$ctx['exceed_amt']
                : max(0, ($ctx['mtd_expense'] ?? 0) - ($ctx['pred_prev'] ?? 0));
            return "Da vuot nguong du bao (+~{$pct}% | ≈ đ ".number_format($amt, 0, ',', '.').").";
        }

        if ($code === 'NEAR_BUDGET') {
            $pct = isset($ctx['remain_pct']) ? round((float)$ctx['remain_pct'])
                : (isset($ctx['ratio']) ? max(0, (1 - $ctx['ratio']) * 100) : 0);
            $amt = isset($ctx['remain_amt']) ? (float)$ctx['remain_amt']
                : max(0, ($ctx['pred_prev'] ?? 0) - ($ctx['mtd_expense'] ?? 0));
            return "Sap cham nguong du bao (con ~{$pct}% | ≈ đ ".number_format($amt, 0, ',', '.').").";
        }

        return "Canh bao chi tieu.";
    }
}
