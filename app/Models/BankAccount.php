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
        'balance' => 'double',
        'is_deleted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
