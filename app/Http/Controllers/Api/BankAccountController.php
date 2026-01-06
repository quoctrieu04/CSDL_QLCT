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
            ->where('is_deleted', 0) // chỉ lấy tài khoản chưa bị xóa
            ->orderByDesc('id')
            ->get();

        return response()->json($accounts);
    }

    /**
     * ✅ Tạo tài khoản ngân hàng mới
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',  // Flutter gửi "name"
            'bankname'    => 'nullable|string|max:255',
            'banknumber'  => 'nullable|string|max:100',
            'initamount'  => 'nullable|numeric|min:0',
            'balance'     => 'nullable|numeric|min:0',
            'currency'    => 'required|string|max:10',
        ]);

        $account = BankAccount::create([
            'user_id'    => $user->id,
            'title'      => $validated['name'],  // map từ "name" sang "title"
            'bankname'   => $validated['bankname'] ?? null,
            'banknumber' => $validated['banknumber'] ?? null,
            'initamount' => $validated['initamount'] ?? 0,
            'balance'    => $validated['balance'] ?? ($validated['initamount'] ?? 0),
            'currency'   => $validated['currency'] ?? 'VND',
            'is_deleted' => 0,
        ]);

        return response()->json($account, 201);
    }

    /**
     * ✅ Cập nhật thông tin tài khoản
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
            'balance'    => 'nullable|numeric|min:0',
            'currency'   => 'nullable|string|max:10',
        ]);

        $account->update([
            'title'      => $validated['name'] ?? $account->title,
            'bankname'   => $validated['bankname'] ?? $account->bankname,
            'banknumber' => $validated['banknumber'] ?? $account->banknumber,
            'balance'    => $validated['balance'] ?? $account->balance,
            'currency'   => $validated['currency'] ?? $account->currency,
        ]);

        return response()->json($account);
    }

    /**
     * ✅ Xóa mềm tài khoản (đặt is_deleted = 1)
     */
    public function destroy($id)
{
    $user = Auth::user();

    $account = BankAccount::where('user_id', $user->id)
        ->where('is_deleted', 0)
        ->findOrFail($id);

    // ✅ Đặt cờ is_deleted = 1 (xóa mềm)
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
     * ⚠️ Tính năng đặt tài khoản mặc định - chưa khả dụng
     */
    public function makeDefault($id)
    {
        return response()->json([
            'message' => 'Tính năng đặt tài khoản mặc định chưa được hỗ trợ (chưa có cột is_default trong DB).'
        ], 501);
    }
}