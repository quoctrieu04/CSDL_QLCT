<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    protected $table = 'saving';

    protected $fillable = [
        'user_id',
        'title',
        'target_amount',
        'current_amount',
        'monthly_amount',
        'start_date',
        'status',
    ];

    public function transactions()
    {
        return $this->hasMany(SavingTransaction::class, 'saving_id');
    }
}
