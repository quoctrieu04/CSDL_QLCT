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
        'term_months',    // số tháng gửi (cho ngân hàng)
        'accountSource',
    ];

    protected $casts = [
        'buy_price'     => 'float',
        'current_price' => 'float',
        'quantity'      => 'float',
        'interest_rate' => 'float',
        'start_date'    => 'date',
        'bank_name'     => 'string',
        'term_months'   => 'integer',  // Chuyển 'term_months' thành kiểu số nguyên
    ];

    // =========================
    // RELATION
    // =========================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function transactions()
{
    return $this->hasMany(InvestmentTransaction::class);
}

}
