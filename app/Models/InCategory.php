<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InCategory extends Model
{
    protected $table = 'in_categories';

    protected $fillable = [
        'user_id',
        'title',
        'balance',
        'currency',
        'month',     // 🆕 thêm tháng
        'year',      // 🆕 thêm năm
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'month'   => 'integer',
        'year'    => 'integer',
    ];

    /**
     * Liên kết với người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Các phiếu thu (invoices)
     */
    public function invoices()
    {
        return $this->hasMany(InInvoice::class, 'incat_id');
    }
}
