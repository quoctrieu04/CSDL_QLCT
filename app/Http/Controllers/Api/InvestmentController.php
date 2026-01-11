<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BankTransaction;
use App\Models\InvestmentTransaction;
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
    $user = $request->user();

    $data = $request->validate([
        'name' => 'required|string',
        'type' => 'required|in:bank,stock',
        'buy_price' => 'required|numeric|min:1',
        'accountSource' => 'required|integer',
        'interest_rate' => 'nullable|numeric',
        'term_months' => 'nullable|integer',
        'start_date' => 'nullable|date',
        'bank_name' => 'nullable|string',
    ]);

    return DB::transaction(function () use ($data, $user) {

    // 1️⃣ LẤY BANK ACCOUNT (đúng tên bảng)
    $bank = DB::table('bankaccounts')
        ->where('id', $data['accountSource'])
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->first();

    if (!$bank) {
        abort(404, 'Bank account not found');
    }

    if ($bank->balance < $data['buy_price']) {
        abort(422, 'Số dư không đủ');
    }

    // 2️⃣ TRỪ TIỀN
    $newBalance = $bank->balance - $data['buy_price'];

    DB::table('bankaccounts')
        ->where('id', $bank->id)
        ->update([
            'balance' => $newBalance,
            'updated_at' => now(),
        ]);

    // 3️⃣ GHI LEDGER (bank_transactions)
    DB::table('bank_transactions')->insert([
        'user_id'     => $user->id,
        'bank_id'     => $bank->id,
        'amount'      => $data['buy_price'],
        'prebalance'  => $bank->balance,
        'operation'   => -1,
        'doc_type'    => 'investment',
        'description' => 'Đầu tư: ' . $data['name'],
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // 4️⃣ TẠO INVESTMENT
    $investment = Investment::create([
        'user_id'        => $user->id,
        'name'           => $data['name'],
        'type'           => $data['type'],
        'buy_price'      => $data['buy_price'],
        'current_price'  => $data['buy_price'],
        'quantity'       => 1,
        'interest_rate'  => $data['interest_rate'],
        'term_months'    => $data['term_months'],
        'start_date'     => $data['start_date'],
        'bank_name'      => $data['bank_name'],
        'accountSource'  => $bank->id,
    ]);

    // 5️⃣ INVESTMENT TRANSACTION
    InvestmentTransaction::create([
        'user_id'       => $user->id,
        'investment_id' => $investment->id,
        'amount'        => $data['buy_price'],
        'operation'     => 1,
        'balance'       => $data['buy_price'],
        'description'   => 'Nhận vốn đầu tư',
    ]);

    return response()->json($investment, 201);
});

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
