<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    /**
     * ✅ Lấy danh sách tài khoản ngân hàng / ví của user hiện tại
     */
    public function index()
    {
        $user = Auth::user();

        $accounts = BankAccount::where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->get();

        return response()->json($accounts);
    }

    /**
     * ✅ Tạo tài khoản ngân hàng mới
     * 🔥 FIX: parse tiền đúng kể cả khi Flutter gửi "3.000.000"
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'bankname'   => 'nullable|string|max:255',
            'banknumber' => 'nullable|string|max:100',
            // ❌ KHÔNG dùng numeric nữa
            'initamount' => 'required',
            'currency'   => 'required|string|max:10',
        ]);

        // ===============================
        // 🔥 CHUẨN HOÁ TIỀN
        // "3.000.000" → 3000000
        // ===============================
        $rawInit = $validated['initamount'];

        if (is_string($rawInit)) {
            $initAmount = (float) str_replace(['.', ','], ['', '.'], $rawInit);
        } else {
            $initAmount = (float) $rawInit;
        }

        $account = BankAccount::create([
            'user_id'    => $user->id,
            'title'      => $validated['name'],
            'bankname'   => $validated['bankname'] ?? null,
            'banknumber' => $validated['banknumber'] ?? null,
            'initamount' => $initAmount,
            'balance'    => $initAmount, // 🔥 SET CHUẨN Ở SERVER
            'currency'   => $validated['currency'],
            'is_deleted' => 0,
        ]);

        return response()->json($account, 201);
    }

    /**
     * ✅ Cập nhật thông tin tài khoản (KHÔNG ĐỘNG TIỀN)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $account = BankAccount::where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'nullable|string|max:255',
            'bankname'   => 'nullable|string|max:255',
            'banknumber' => 'nullable|string|max:100',
            'currency'   => 'nullable|string|max:10',
        ]);

        $account->update([
            'title'      => $validated['name'] ?? $account->title,
            'bankname'   => $validated['bankname'] ?? $account->bankname,
            'banknumber' => $validated['banknumber'] ?? $account->banknumber,
            'currency'   => $validated['currency'] ?? $account->currency,
        ]);

        return response()->json($account);
    }

    /**
     * ✅ Xóa mềm tài khoản
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $account = BankAccount::where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->findOrFail($id);

        $account->update(['is_deleted' => 1]);

        return response()->json(['message' => 'Xóa tài khoản thành công']);
    }

    /**
     * ✅ Khôi phục tài khoản đã xóa mềm
     */
    public function restore($id)
    {
        $user = Auth::user();

        $account = BankAccount::where('user_id', $user->id)
            ->where('is_deleted', 1)
            ->findOrFail($id);

        $account->update(['is_deleted' => 0]);

        return response()->json(['message' => 'Đã khôi phục tài khoản thành công']);
    }

    /**
     * ⚠️ Tính năng đặt tài khoản mặc định - chưa hỗ trợ
     */
    public function makeDefault($id)
    {
        return response()->json([
            'message' => 'Tính năng đặt tài khoản mặc định chưa được hỗ trợ'
        ], 501);
    }
}
