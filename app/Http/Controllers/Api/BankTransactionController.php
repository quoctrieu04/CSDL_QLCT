<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\InInvoice;
use App\Models\OutInvoice;

class BankTransactionController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'bankaccount_id' => 'required|integer|exists:bankaccounts,id',
            'amount' => 'required|numeric|min:1',
            'direction' => 'required|in:in,out',
            'description' => 'nullable|string|max:300',
            'category_id' => 'nullable|integer', // nếu có danh mục
        ]);

        // Lấy tài khoản
        $account = BankAccount::findOrFail($data['bankaccount_id']);
        $prebalance = $account->balance;

        DB::beginTransaction();
        try {
            if ($data['direction'] === 'out') {
                // ➖ 1. Tạo phiếu chi
                $out = OutInvoice::create([
                    'user_id' => $r->user()->id,
                    'outcat_id' => $data['category_id'] ?? null,
                    'banktrans_id' => null,
                    'amount' => $data['amount'],
                    'doc_type' => 'OUT',
                    'content' => $data['description'] ?? null,
                ]);

                // ➖ 2. Ghi giao dịch
                $txn = BankTransaction::create([
                    'user_id' => $r->user()->id,
                    'doc_id' => $out->id,
                    'doc_type' => 'OUT',
                    'bank_id' => $account->id,
                    'amount' => $data['amount'],
                    'prebalance' => $prebalance,
                    'operation' => '-',
                    'description' => $data['description'] ?? null,
                ]);

                // ➖ 3. Cập nhật số dư
                $account->balance = $prebalance - $data['amount'];
                $account->save();
            } else {
                // ➕ 1. Tạo phiếu thu
                $in = InInvoice::create([
                    'user_id' => $r->user()->id,
                    'incat_id' => $data['category_id'] ?? null,
                    'banktrans_id' => null,
                    'amount' => $data['amount'],
                    'content' => $data['description'] ?? null,
                ]);

                // ➕ 2. Ghi giao dịch
                $txn = BankTransaction::create([
                    'user_id' => $r->user()->id,
                    'doc_id' => $in->id,
                    'doc_type' => 'IN',
                    'bank_id' => $account->id,
                    'amount' => $data['amount'],
                    'prebalance' => $prebalance,
                    'operation' => '+',
                    'description' => $data['description'] ?? null,
                ]);

                // ➕ 3. Cập nhật số dư
                $account->balance = $prebalance + $data['amount'];
                $account->save();
            }

            DB::commit();
            return response()->json(['data' => $txn, 'message' => 'Giao dịch thành công'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Lỗi khi lưu giao dịch: '.$e->getMessage()], 500);
        }
    }
}
