<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OutInvoice;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetInTransaction;
use Carbon\Carbon;

class OutInvoiceController extends Controller
{
    /**
     * 💵 Tạo phiếu chi (có hỗ trợ ngày thực tế phát sinh occurred_at)
     */
    public function store(Request $r)
    {
        $input = [
            'bank_id'     => $r->input('bank_id') ?? $r->input('bankId'),
            'outcat_id'   => $r->input('outcat_id')
                ?? $r->input('outcatId')
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
            'outcat_id'   => 'nullable|integer|exists:out_categories,id',
            'amount'      => 'required|numeric|min:1',
            'content'     => 'nullable|string|max:300',
            'month'       => 'nullable|integer|min:1|max:12',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'occurred_at' => 'nullable|date',
        ])->validate();

        \Log::info('✅ OutInvoice dữ liệu sau validate', $data);

        $account = BankAccount::findOrFail($data['bank_id']);
        $prebalance = $account->balance;
        $userId = $r->user()->id ?? 1;

        $date = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        $data['month'] = $data['month'] ?? $date->month;
        $data['year']  = $data['year'] ?? $date->year;

        DB::beginTransaction();
        try {
            // 1️⃣ Tạo phiếu chi
            $invoice = OutInvoice::create([
                'user_id'      => $userId,
                'outcat_id'    => $data['outcat_id'] ?? null,
                'banktrans_id' => null,
                'amount'       => $data['amount'],
                'month'        => $data['month'],
                'year'         => $data['year'],
                'doc_type'     => 'OUT',
                'content'      => $data['content'] ?? null,
                'occurred_at'  => $date,
            ]);

            // 2️⃣ Giao dịch ngân hàng
            $txn = BankTransaction::create([
                'user_id'    => $userId,
                'doc_id'     => $invoice->id,
                'doc_type'   => 'OUT',
                'bank_id'    => $data['bank_id'],
                'amount'     => $data['amount'],
                'prebalance' => $prebalance,
                'operation'  => -1,
                'description'=> $data['content'] ?? null,
                'created_at' => $date,
            ]);

            $invoice->update(['banktrans_id' => $txn->id]);
            $account->update(['balance' => $prebalance - $data['amount']]);

            // 3️⃣ Ghi chi tiêu vào ngân sách
            if (!empty($data['outcat_id'])) {
                $budget = Budget::firstOrCreate(
                    [
                        'user_id'     => $userId,
                        'category_id' => $data['outcat_id'],
                    ],
                    [
                        'title'  => 'Tự động ' . $data['outcat_id'],
                        'amount' => 0,
                    ]
                );

                BudgetInTransaction::create([
                    'user_id'            => $userId,
                    'budget_id'          => $budget->id,
                    'operation'          => -1,
                    'amount'             => $data['amount'],
                    'prebalance'         => $budget->amount,
                    'month'              => $data['month'],
                    'year'               => $data['year'],
                    'banktransaction_id' => $txn->id,
                    'created_at'         => $date,
                ]);

                $budget->amount = max(0, $budget->amount - $data['amount']);
                $budget->save();
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'invoice' => $invoice,
                    'bank_transaction' => $txn,
                ],
                'message' => '✅ Đã thêm phiếu chi thành công (có ngày phát sinh)',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('💥 OutInvoice store error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📅 Danh sách chi tiêu theo tháng/năm/ngày (trả luôn occurred_at ISO format)
     */
    public function index(Request $r)
    {
        $userId = $r->user()->id ?? 1;
        $month = (int) $r->input('month', now()->month);
        $year  = (int) $r->input('year', now()->year);
        $day   = $r->input('day');

        $spending = DB::table('out_invoices as o')
            ->join('bank_transactions as t', 'o.banktrans_id', '=', 't.id')
            ->leftJoin('bankaccounts as b', 't.bank_id', '=', 'b.id')
            ->leftJoin('out_categories as c', 'o.outcat_id', '=', 'c.id')
            ->select(
                'o.id',
                'o.amount',
                'o.content',
                'o.occurred_at',
                'o.created_at',
                'c.title as category_name',
                'b.title as bank_name'
            )
            ->where('o.user_id', $userId)
            ->whereYear('o.occurred_at', $year)
            ->whereMonth('o.occurred_at', $month)
            ->when($day, fn($q) => $q->whereDay('o.occurred_at', $day))
            ->orderByDesc('o.occurred_at')
            ->get()
            ->map(function ($row) {
                $row->occurred_at = Carbon::parse($row->occurred_at)->toIso8601String();
                $row->created_at  = Carbon::parse($row->created_at)->toIso8601String();
                return $row;
            });

        $total = $spending->sum('amount');

        return response()->json([
            'data' => $spending,
            'total' => $total,
            'month' => $month,
            'year' => $year,
            'day' => $day,
            'message' => $day
                ? "Chi tiêu ngày $day/$month/$year"
                : "Chi tiêu tháng $month/$year",
        ]);
    }
}
