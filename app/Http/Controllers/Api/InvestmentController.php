<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investment;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    /**
     * Danh sách đầu tư của user hiện tại
     */
    public function index(Request $request)
    {
        return Investment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Tạo khoản đầu tư mới
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:bank,stock',

            'buy_price' => 'required|numeric|min:0',
            'current_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',

            // Bank only
            'interest_rate' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'bank_name' => 'nullable|string',
        ]);

        $data['user_id'] = $request->user()->id;

        // =========================
        // CHUẨN HOÁ THEO TYPE
        // =========================

        if ($data['type'] === 'bank') {
            $data['current_price'] = $data['buy_price'];
            $data['quantity'] = 1;
        }

        if ($data['type'] === 'stock') {
            $data['quantity'] = $data['quantity'] ?? 0;
            $data['current_price'] = $data['current_price'] ?? $data['buy_price'];
        }

        $investment = Investment::create($data);

        return response()->json($investment, 201);
    }

    /**
     * Cập nhật giá hiện tại (CHỈ dùng cho cổ phiếu)
     */
    public function update(Request $request, $id)
    {
        $investment = Investment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($investment->type !== 'stock') {
            return response()->json([
                'message' => 'Chỉ được cập nhật giá cho cổ phiếu'
            ], 422);
        }

        $data = $request->validate([
            'current_price' => 'required|numeric|min:0',
        ]);

        $investment->update([
            'current_price' => $data['current_price'],
        ]);

        return response()->json($investment);
    }

    /**
     * Xoá khoản đầu tư
     */
    public function destroy(Request $request, $id)
    {
        $investment = Investment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $investment->delete();

        return response()->json(['message' => 'deleted']);
    }
}
