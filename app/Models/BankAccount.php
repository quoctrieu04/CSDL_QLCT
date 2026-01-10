<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bankaccounts';

    protected $fillable = [
        'user_id',
        'title',
        'bankname',
        'banknumber',
        'initamount',
        'balance',
        'currency',
        'is_deleted',
    ];

    protected $casts = [
        'initamount' => 'double',
        'balance'    => 'double',
        'is_deleted' => 'boolean',
    ];

    /**
     * 🔥 TỰ ĐỘNG GÁN BALANCE = INITAMOUNT KHI TẠO
     * → Chống quên, chống lỗi nghiệp vụ
     */
    protected static function booted()
    {
        static::creating(function ($account) {
            if ($account->balance === null) {
                $account->balance = $account->initamount ?? 0;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
