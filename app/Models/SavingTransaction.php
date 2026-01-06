<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingTransaction extends Model
{
    protected $table = 'saving_transaction';

    protected $fillable = [
        'user_id',
        'saving_id',
        'bank_id',
        'amount',
        'date',
        'note'
    ];
}
