<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InInvoice;
use App\Models\OutInvoice;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\InCategory;
use App\Models\OutCategory;

class TransactionController extends Controller
{
    /**
     * 💵 Ghi giao dịch thu/chi
     */
    public function store(Request $r)
    {
        \Log::info('🔥 Transaction Input:', $r->all());
        $user = $r->user();

        // ✅ Validate dữ liệu đầu vào
        $validated = $r->validate([
            'type'        => 'required|in:in,out',
            'bank_id'     => 'required|exists:bankaccounts,id',
            'category_id' => 'nullable|integer',
            'amount'      => 'required|numeric|min:1',
            'content'     => 'nullable|string|max:300',
            'month'       => 'nullable|integer|min:1|max:12',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'occurred_at' => 'nullable|date', // 🟢 thêm validate ngày phát sinh
        ]);

        return DB::transaction(function () use ($user, $validated) {
            // 🔒 Khóa ví để tránh race condition
            $bank = BankAccount::where('id', $validated['bank_id'])
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $prebalance = $bank->balance;
            $amount     = $validated['amount'];

            // 🚫 Kiểm tra số dư khi chi
            if ($validated['type'] === 'out' && $prebalance < $amount) {
                abort(422, 'Số dư không đủ để chi.');
            }

            // 🏦 Giao dịch ngân hàng (BankTransaction)
            $operation = $validated['type'] === 'in' ? 1 : -1;
            $bankTrans = BankTransaction::create([
                'user_id'    => $user->id,
                'doc_type'   => $validated['type'] === 'in' ? 'in_invoice' : 'out_invoice',
                'bank_id'    => $validated['bank_id'],
                'amount'     => $amount,
                'prebalance' => $prebalance,
                'operation'  => $operation,
            ]);

            /* =====================================================
               📥 PHIẾU THU (IN)
            ===================================================== */
            if ($validated['type'] === 'in') {
                $invoice = InInvoice::create([
                    'user_id'      => $user->id,
                    'incat_id'     => $validated['category_id'],
                    'banktrans_id' => $bankTrans->id,
                    'amount'       => $amount,
                    'content'      => $validated['content'],
                    'month'        => $validated['month'] ?? now()->month,
                    'year'         => $validated['year'] ?? now()->year,
                    'occurred_at'  => $validated['occurred_at'] ?? now(), // 🟢 thêm
                ]);

                // ➕ Cộng tiền vào danh mục thu
                if (!empty($validated['category_id'])) {
                    InCategory::where('id', $validated['category_id'])
                        ->where('user_id', $user->id)
                        ->increment('balance', $amount);
                }
            }

            /* =====================================================
               📤 PHIẾU CHI (OUT)
            ===================================================== */
            else {
                // 1️⃣ Tạo phiếu chi
                $invoice = OutInvoice::create([
                    'user_id'      => $user->id,
                    'outcat_id'    => $validated['category_id'],
                    'banktrans_id' => $bankTrans->id,
                    'amount'       => $amount,
                    'doc_type'     => 'OUT',
                    'doctrans_id'  => null,
                    'content'      => $validated['content'],
                    'month'        => $validated['month'] ?? now()->month,
                    'year'         => $validated['year'] ?? now()->year,
                    'occurred_at'  => $validated['occurred_at'] ?? now(), // 🟢 thêm
                ]);

                // 2️⃣ Cập nhật liên kết hai chiều
                $invoice->update(['doctrans_id' => $bankTrans->id]);
                $bankTrans->update(['doc_id' => $invoice->id]);
            }

            // 3️⃣ Cập nhật số dư ví
            $bank->balance += ($operation * $amount);
            $bank->save();

            return response()->json([
                'message'          => '✅ Giao dịch thành công',
                'invoice'          => $invoice,
                'bank_transaction' => $bankTrans,
                'new_balance'      => $bank->balance,
            ], 201);
        });
    }

    /**
     * 📊 Thống kê chi tiêu theo danh mục
     */
    public function spentByCategory(Request $r)
    {
        $userId = $r->user()->id ?? 1;
        $month  = (int) $r->query('month', now()->month);
        $year   = (int) $r->query('year', now()->year);

        $data = DB::table('out_invoices as o')
            ->leftJoin('out_categories as c', 'o.outcat_id', '=', 'c.id')
            ->select(
                'o.outcat_id as category_id',
                'c.title as category_name',
                DB::raw('SUM(o.amount) as spent')
            )
            ->where('o.user_id', $userId)
            ->where('o.month', '=', $month)
            ->where('o.year', '=', $year)
            ->groupBy('o.outcat_id', 'c.title')
            ->get();

        return response()->json([
            'data'    => $data,
            'message' => "📊 Thống kê chi tiêu theo danh mục tháng $month/$year",
        ]);
    }
}
