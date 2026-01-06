<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
      protected $fillable = [
        'user_id',
        'name',
        'type',
        'buy_price',
        'current_price',
        'quantity',
        'total_invested',
        'profit_loss',
        'auto_update',
        'symbol',
        'api_source',
        'api_field',
        'api_path',
    ];

    protected $casts = [
        'buy_price' => 'float',
        'current_price' => 'float',
        'quantity' => 'float',
        'total_invested' => 'float',
        'profit_loss' => 'float',
        'auto_update' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
