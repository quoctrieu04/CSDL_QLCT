<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InInvoice;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use Carbon\Carbon;

class InInvoiceController extends Controller
{
    /**
     * 💰 Tạo phiếu thu (có hỗ trợ ngày phát sinh occurred_at)
     */
    public function store(Request $r)
    {
        $input = [
            'bank_id'     => $r->input('bank_id') ?? $r->input('bankId'),
            'incat_id'    => $r->input('incat_id')
                ?? $r->input('incatId')
                ?? $r->input('category_id')
                ?? $r->input('categoryId'),
            'amount'      => $r->input('amount'),
            'content'     => $r->input('content'),
            'month'       => $r->input('month'),
            'year'        => $r->input('year'),
            'occurred_at' => $r->input('occurred_at'),
        ];

        $data = validator($input, [
            'bank_id'     => 'required|exists:bankaccounts,id',
            'incat_id'    => 'nullable|integer|exists:in_categories,id',
            'amount'      => 'required|numeric|min:1',
            'content'     => 'nullable|string|max:300',
            'month'       => 'nullable|integer|min:1|max:12',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'occurred_at' => 'nullable|date',
        ])->validate();

        $account = BankAccount::findOrFail($data['bank_id']);
        $prebalance = $account->balance;
        $userId = $r->user()->id ?? 1;

        // 🕓 Ngày thực tế phát sinh
        $date = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        $data['month'] = $data['month'] ?? $date->month;
        $data['year']  = $data['year'] ?? $date->year;

        DB::beginTransaction();
        try {
            // 1️⃣ Tạo phiếu thu
            $invoice = InInvoice::create([
                'user_id'      => $userId,
                'incat_id'     => $data['incat_id'] ?? null,
                'banktrans_id' => null,
                'amount'       => $data['amount'],
                'month'        => $data['month'],
                'year'         => $data['year'],
                'doc_type'     => 'IN',
                'content'      => $data['content'] ?? null,
                'occurred_at'  => $date,
            ]);

            // 2️⃣ Ghi giao dịch ngân hàng
            $txn = BankTransaction::create([
                'user_id'    => $userId,
                'doc_id'     => $invoice->id,
                'doc_type'   => 'IN',
                'bank_id'    => $data['bank_id'],
                'amount'     => $data['amount'],
                'prebalance' => $prebalance,
                'operation'  => 1,
                'description'=> $data['content'] ?? null,
                'created_at' => $date,
            ]);

            // 3️⃣ Liên kết giao dịch
            $invoice->update(['banktrans_id' => $txn->id]);

            // 4️⃣ Cộng tiền vào tài khoản
            $account->update(['balance' => $prebalance + $data['amount']]);

            DB::commit();

            return response()->json([
                'data' => [
                    'invoice' => $invoice,
                    'bank_transaction' => $txn,
                ],
                'message' => '✅ Đã thêm phiếu thu thành công (có ngày phát sinh)',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('💥 InInvoice store error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📅 Danh sách phiếu thu theo tháng/năm/ngày
     */
    public function index(Request $r)
    {
        $userId = $r->user()->id ?? 1;
        $month = (int) $r->input('month', now()->month);
        $year  = (int) $r->input('year', now()->year);
        $day   = $r->input('day');

        $incomes = DB::table('in_invoices as i')
            ->join('bank_transactions as t', 'i.banktrans_id', '=', 't.id')
            ->leftJoin('bankaccounts as b', 't.bank_id', '=', 'b.id')
            ->leftJoin('in_categories as c', 'i.incat_id', '=', 'c.id')
            ->select(
                'i.id',
                'i.amount',
                'i.content',
                'i.occurred_at',
                'i.created_at',
                'c.title as category_name',
                'b.title as bank_name'
            )
            ->where('i.user_id', $userId)
            ->whereYear('i.occurred_at', $year)
            ->whereMonth('i.occurred_at', $month)
            ->when($day, fn($q) => $q->whereDay('i.occurred_at', $day))
            ->orderByDesc('i.occurred_at')
            ->get()
            ->map(function ($row) {
                // Trả về định dạng ISO8601 để Flutter parse DateTime dễ hơn
                $row->occurred_at = $row->occurred_at
                    ? Carbon::parse($row->occurred_at)->toIso8601String()
                    : null;
                $row->created_at = $row->created_at
                    ? Carbon::parse($row->created_at)->toIso8601String()
                    : null;
                return $row;
            });

        $total = $incomes->sum('amount');

        return response()->json([
            'data' => $incomes,
            'total' => $total,
            'month' => $month,
            'year' => $year,
            'day' => $day,
            'message' => $day
                ? "Khoản thu ngày $day/$month/$year"
                : "Khoản thu tháng $month/$year",
        ]);
    }
}
