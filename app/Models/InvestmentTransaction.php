<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InvestmentTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'investment_id',
        'amount',
        'operation',
        'balance',
        'description',
    ];

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Lấy số dư đầu tư hiện tại
     */
    public static function latestBalance(int $userId, int $investmentId): float
    {
        $last = self::query()
            ->where('user_id', $userId)
            ->where('investment_id', $investmentId)
            ->orderByDesc('id')
            ->first();

        return $last ? (float)$last->balance : 0;
    }

    /**
     * Ghi ledger đầu tư an toàn
     */
    public static function createLedger(array $data): self
    {
        return DB::transaction(function () use ($data) {

            $last = self::query()
                ->where('user_id', $data['user_id'])
                ->where('investment_id', $data['investment_id'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $current = $last ? (float)$last->balance : 0;

            if ($data['operation'] === -1 && $current < $data['amount']) {
                throw new \RuntimeException('Không đủ tiền trong khoản đầu tư');
            }

            $data['balance'] = $current + ($data['operation'] * $data['amount']);

            return self::create($data);
        });
    }
    
}
