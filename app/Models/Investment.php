<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',           // bank | stock

        'buy_price',      // bank: tiền gốc | stock: giá mua
        'current_price',  // bank = buy_price | stock: giá hiện tại
        'quantity',       // bank = 1 | stock = số lượng

        // Bank only
        'interest_rate',  // % / năm
        'start_date',     // ngày gửi
        'bank_name',      // tên ngân hàng
    ];

    protected $casts = [
        'buy_price'     => 'float',
        'current_price' => 'float',
        'quantity'      => 'float',
        'interest_rate' => 'float',
        'start_date'    => 'date',
        'bank_name'    => 'string',
    ];

    // =========================
    // RELATION
    // =========================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
