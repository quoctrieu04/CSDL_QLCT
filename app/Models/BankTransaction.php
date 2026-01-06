<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $table = 'bank_transactions';

    protected $fillable = [
        'user_id',
        'doc_id',
        'doc_type',
        'bank_id',
        'amount',
        'prebalance',
        'operation',
        'description',
    ];

    // 🔗 Giao dịch có thể là phiếu thu hoặc phiếu chi
    public function inInvoice()
    {
        return $this->belongsTo(InInvoice::class, 'doc_id')->where('doc_type', 'IN');
    }

    public function outInvoice()
    {
        return $this->belongsTo(OutInvoice::class, 'doc_id')->where('doc_type', 'OUT');
    }

    // 🔗 Ví / tài khoản
    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_id');
    }
}
