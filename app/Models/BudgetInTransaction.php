<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetInTransaction extends Model
{
    use HasFactory;

    protected $table = 'budget_in_transactions';

    protected $fillable = [
        'user_id',
        'budget_id',
        'operation',
        'amount',
        'prebalance',
        'month',
        'year',
        'banktransaction_id',
    ];

    // ✅ Bắt buộc thêm dòng này
    public $timestamps = true;

    /** 
     * 🚦 Hằng số biểu thị loại giao dịch
     * 1 = allocate (phân bổ) | -1 = spend (chi tiêu)
     */
    const OP_ALLOCATE = 1;
    const OP_SPEND = -1;

    /** 🔗 Quan hệ: thuộc về Budget */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /** 💳 Quan hệ: liên kết đến BankTransaction */
    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'banktransaction_id');
    }

    /** 👤 Quan hệ: thuộc về User */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 📊 Scope: lọc theo loại giao dịch */
    public function scopeOperation($query, int $type)
    {
        return $query->where('operation', $type);
    }

    /** 🏷️ Accessor: trả tên loại giao dịch */
    public function getOperationLabelAttribute(): string
    {
        return match ($this->operation) {
            self::OP_ALLOCATE => 'allocate',
            self::OP_SPEND => 'spend',
            default => 'unknown',
        };
    }

    /** 🧮 Accessor: số tiền có dấu */
    public function getSignedAmountAttribute(): float
    {
        return $this->amount * $this->operation;
    }
}
