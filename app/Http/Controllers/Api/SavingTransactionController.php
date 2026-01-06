<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

class SavingTransactionController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            'saving_id' => 'required|integer',
            'bank_id'   => 'required|integer',
            'amount'    => 'required|numeric',
            'note'      => 'nullable|string',
        ]);

        $userId = auth()->id();
        $savingId = $req->saving_id;

        return DB::transaction(function () use ($req, $userId, $savingId) {

            // 1. Lấy saving
            $saving = Saving::where('id', $savingId)
                            ->where('user_id', $userId)
                            ->firstOrFail();

            // 2. Lấy ví
            $wallet = BankAccount::where('id', $req->bank_id)
                                ->where('user_id', $userId)
                                ->firstOrFail();

            // 3. Tạo giao dịch
            $tx = SavingTransaction::create([
                'user_id'    => $userId,
                'saving_id'  => $savingId,
                'bank_id'    => $req->bank_id,
                'amount'     => $req->amount,
                'date'       => now()->toDateString(),
                'note'       => $req->note,
            ]);

            // 4. Cập nhật ví
            // amount > 0 → nạp vào savings → trừ tiền ví
            // amount < 0 → rút savings → cộng tiền ví
            $wallet->balance -= $req->amount;
            $wallet->save();

            // 5. Cập nhật saving
            $saving->current_amount += $req->amount;

            // hoàn thành mục tiêu → finished
            if ($saving->current_amount >= $saving->target_amount) {
                $saving->status = 'finished';
            }

            $saving->save();

            return response()->json([
                'success' => true,
                'transaction' => $tx,
                'saving' => $saving,
                'wallet' => $wallet
            ]);
        });
    }
}
