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

            // Add term_months for bank investments
            'term_months' => 'nullable|integer|min:1',  // Chỉ cần yêu cầu nếu là loại "bank"
            'accountSource' => 'nullable|string',
        ]);

        $data['user_id'] = $request->user()->id;

        // =========================
        // CHUẨN HOÁ THEO TYPE
        // =========================

        // Kiểm tra loại "bank"
        if ($data['type'] === 'bank') {
            // Đảm bảo giá trị `current_price` = `buy_price` cho ngân hàng
            $data['current_price'] = $data['buy_price'];
            $data['quantity'] = 1;

            // Nếu là ngân hàng, thêm term_months và kiểm tra kỳ hạn gửi
            if (isset($data['term_months']) && $data['term_months'] <= 0) {
                return response()->json(['message' => 'Kỳ hạn gửi phải lớn hơn 0'], 422);
            }

            // Kiểm tra nếu `bank_name` trống khi là loại ngân hàng
            if (empty($data['bank_name'])) {
                return response()->json(['message' => 'Tên ngân hàng không được để trống'], 422);
            }
        }

        // Kiểm tra loại "stock"
        if ($data['type'] === 'stock') {
            // Kiểm tra và gán `quantity` mặc định nếu không có
            $data['quantity'] = $data['quantity'] ?? 0;
            // Nếu không có `current_price`, gán giá trị là `buy_price`
            $data['current_price'] = $data['current_price'] ?? $data['buy_price'];
        }

        // Tạo mới khoản đầu tư
        try {
            $investment = Investment::create($data);
            return response()->json($investment, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi tạo khoản đầu tư', 'error' => $e->getMessage()], 500);
        }
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
