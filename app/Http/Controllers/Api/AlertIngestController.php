<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alert;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class AlertIngestController extends Controller
{
    public function store(Request $req)
    {
        $validated = $req->validate([
            'alerts' => 'required|array',
            'alerts.*.user_id' => 'required|integer',
            'alerts.*.scope'   => 'required|string',
            'alerts.*.code'    => 'required|string',
            'alerts.*.level'   => 'required|string|in:info,warning,critical',
            'alerts.*.context' => 'required|array',
            'alerts.*.hash'    => 'required|string',
        ]);

        $alerts = $validated['alerts'];

        // Lấy trước các hash đã tồn tại để phân biệt created/updated
        $hashes   = array_values(array_unique(Arr::pluck($alerts, 'hash')));
        $existing = Alert::whereIn('hash', $hashes)->pluck('id', 'hash');

        $now = Carbon::now('UTC');

        // Chuẩn hóa dữ liệu để upsert (upsert không chạy cast của Model)
        $rows = array_map(function ($a) use ($now) {
            return [
                'user_id'    => $a['user_id'],
                'scope'      => $a['scope'],
                'code'       => $a['code'],
                'level'      => $a['level'],
                'context'    => json_encode($a['context'], JSON_UNESCAPED_UNICODE),
                'hash'       => $a['hash'],
                'status'     => $a['status'] ?? 'new',
                'created_at' => $now,        // khi update sẽ không dùng giá trị này
                'updated_at' => $now,
            ];
        }, $alerts);

        // Upsert theo hash: trùng -> cập nhật context/level/status/updated_at
        Alert::upsert($rows, ['hash'], ['level', 'context', 'status', 'updated_at']);

        // Thống kê
        $created = 0; $updated = 0;
        foreach ($alerts as $a) {
            if ($existing->has($a['hash'])) $updated++;
            else $created++;
        }

        // Đóng (resolved) các cảnh báo cũ cùng user/scope/code trong tháng hiện tại
        foreach ($alerts as $a) {
            Alert::where('user_id', $a['user_id'])
                ->where('scope', $a['scope'])
                ->where('code',  $a['code'])
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->where('hash', '!=', $a['hash']) // khác bản vừa upsert
                ->update(['status' => 'resolved']);
            // Nếu muốn xóa hẳn thay vì "resolved", dùng ->delete() ở dòng trên.
        }

        return response()->json([
            'ok'       => true,
            'created'  => $created,
            'updated'  => $updated,
        ]);
    }
}
