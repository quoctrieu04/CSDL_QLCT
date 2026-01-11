<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'bank_id',
        'doc_id',
        'doc_type',
        'amount',
        'prebalance',
        'operation',
        'description',
    ];

    public static function createLedger(array $data)
    {
        return DB::transaction(function () use ($data) {

            $bank = BankAccount::lockForUpdate()->findOrFail($data['bank_id']);

            $prebalance = $bank->balance;

            // 1️⃣ CẬP NHẬT SỐ DƯ TÀI KHOẢN
            if ($data['operation'] === -1) {
                $bank->balance -= $data['amount'];
            } else {
                $bank->balance += $data['amount'];
            }

            $bank->save();

            // 2️⃣ GHI LEDGER (LƯU SỐ DƯ TRƯỚC)
            return self::create([
                'user_id'     => $data['user_id'],
                'bank_id'     => $data['bank_id'],
                'doc_id'      => $data['doc_id'] ?? null,
                'doc_type'    => $data['doc_type'],
                'amount'      => $data['amount'],
                'prebalance'  => $prebalance,
                'operation'   => $data['operation'],
                'description' => $data['description'] ?? null,
            ]);
        });
    }
}
