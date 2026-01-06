<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InInvoice extends Model
{
    use HasFactory;

    protected $table = 'in_invoices';

    protected $fillable = [
        'user_id',
        'incat_id',
        'banktrans_id',
        'amount',
        'content',
        'occurred_at',
    ];

    // 🔗 Quan hệ đến giao dịch ngân hàng
    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'banktrans_id');
    }

    // 🔗 Người dùng
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Danh mục thu nhập (nếu có)
    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'incat_id');
    }
}
