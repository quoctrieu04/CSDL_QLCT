<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BankTransaction;
use App\Models\InvestmentTransaction;
use App\Models\Investment;
use Carbon\Carbon;

class InvestmentController extends Controller
{
    /**
     * Danh sách đầu tư
     */
    public function index(Request $request)
    {
        return Investment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Tạo khoản đầu tư
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'          => 'required|string',
            'type'          => 'required|in:bank,stock',
            'buy_price'     => 'required|numeric|min:1',
            'accountSource' => 'required|integer',
            'interest_rate' => 'nullable|numeric',
            'term_months'   => 'nullable|integer',
            'start_date'    => 'nullable|date',
            'bank_name'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data, $user) {

            // 1️⃣ Lock bank account
            $bank = DB::table('bankaccounts')
                ->where('id', $data['accountSource'])
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bank->balance < $data['buy_price']) {
                abort(422, 'Số dư không đủ');
            }

            // 2️⃣ Trừ tiền
            DB::table('bankaccounts')
                ->where('id', $bank->id)
                ->update([
                    'balance'    => $bank->balance - $data['buy_price'],
                    'updated_at' => now(),
                ]);

            // 3️⃣ Ledger ngân hàng
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

            // 4️⃣ Tạo investment
            $investment = Investment::create([
                'user_id'       => $user->id,
                'name'          => $data['name'],
                'type'          => $data['type'],
                'buy_price'     => $data['buy_price'],
                'current_price' => $data['buy_price'],
                'quantity'      => 1,
                'interest_rate' => $data['interest_rate'],
                'term_months'   => $data['term_months'],
                'start_date'    => $data['start_date'],
                'bank_name'     => $data['bank_name'],
                'accountSource' => $bank->id,
            ]);

            // 5️⃣ Investment transaction
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
     * Cập nhật giá cổ phiếu
     */
    public function update(Request $request, $id)
    {
        $investment = Investment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($investment->type !== 'stock') {
            abort(422, 'Chỉ dùng cho cổ phiếu');
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
     * ❌ Không cho xoá đầu tư ngân hàng / BĐS
     */
    public function destroy(Request $request, $id)
    {
        abort(403, 'Không cho xoá đầu tư ngân hàng / BĐS');
    }

    /**
     * ✅ RÚT TIỀN ĐẦU TƯ (LÃI / TOÀN BỘ)
     * Route: POST /investments/{investment}/withdraw
     */
    public function withdraw(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'receive_account_id' => 'required|integer',
            'withdraw_type'      => 'required|in:interest,all',
            'amount'             => 'nullable|numeric|min:1',
        ]);

        return DB::transaction(function () use ($data, $user, $request) {

            // 1️⃣ LẤY INVESTMENT TỪ ROUTE + LOCK
            $investmentId = $request->route('investment');

            $investment = Investment::where('id', $investmentId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($investment->closed_at !== null) {
                abort(422, 'Khoản đầu tư đã tất toán');
            }

            if ($investment->type !== 'bank') {
                abort(422, 'Chỉ hỗ trợ rút tiền cho đầu tư ngân hàng');
            }

            // 2️⃣ LẤY BANK NHẬN TIỀN
            $bank = DB::table('bankaccounts')
                ->where('id', $data['receive_account_id'])
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 3️⃣ TÍNH LÃI PHÁT SINH
            $start = Carbon::parse($investment->start_date);
            $now   = Carbon::now();

            $months = max(0, $start->diffInMonths($now));

            $totalInterest = $investment->buy_price
                * ($investment->interest_rate / 100)
                * $months;

            // 4️⃣ LÃI ĐÃ RÚT
            $withdrawnInterest = InvestmentTransaction::where('investment_id', $investment->id)
                ->where('operation', -1)
                ->where('description', 'like', '%Rút lãi%')
                ->sum('amount');

            $remainingInterest = max(0, $totalInterest - $withdrawnInterest);

            // 5️⃣ XÁC ĐỊNH SỐ TIỀN RÚT
            if ($data['withdraw_type'] === 'interest') {

                if ($remainingInterest <= 0) {
                    abort(422, 'Không còn lãi để rút');
                }

                if (empty($data['amount'])) {
                    abort(422, 'Vui lòng nhập số tiền rút lãi');
                }

                if ($data['amount'] > $remainingInterest) {
                    abort(422, 'Số tiền rút vượt quá lãi còn lại');
                }

                $amount = $data['amount'];
                $description = 'Rút lãi đầu tư';

            } else {
                // 🔒 RÚT TOÀN BỘ
                $amount = $investment->buy_price + $remainingInterest;
                $description = 'Rút toàn bộ vốn + lãi';
                $investment->closed_at = now();
            }

            // 6️⃣ CỘNG TIỀN VÀO BANK
            DB::table('bankaccounts')
                ->where('id', $bank->id)
                ->update([
                    'balance'    => $bank->balance + $amount,
                    'updated_at' => now(),
                ]);

            // 7️⃣ BANK TRANSACTION
            DB::table('bank_transactions')->insert([
                'user_id'     => $user->id,
                'bank_id'     => $bank->id,
                'amount'      => $amount,
                'prebalance'  => $bank->balance,
                'operation'   => 1,
                'doc_type'    => 'investment_withdraw',
                'description' => $description . ': ' . $investment->name,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 8️⃣ INVESTMENT TRANSACTION
            InvestmentTransaction::create([
                'user_id'       => $user->id,
                'investment_id' => $investment->id,
                'amount'        => $amount,
                'operation'     => -1,
                'balance'       => $remainingInterest - (
                    $data['withdraw_type'] === 'interest'
                        ? $amount
                        : $remainingInterest
                ),
                'description'   => $description,
            ]);

            // 9️⃣ LƯU INVESTMENT
            $investment->save();

            return response()->json([
                'success'            => true,
                'withdraw_amount'    => $amount,
                'remaining_interest' => $remainingInterest - (
                    $data['withdraw_type'] === 'interest'
                        ? $amount
                        : $remainingInterest
                ),
            ]);
        });
    }
}
