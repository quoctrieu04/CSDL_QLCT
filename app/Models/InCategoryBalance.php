<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InCategoryBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'year',
        'month',
        'amount',
    ];

    public function category()
    {
        return $this->belongsTo(InCategory::class, 'category_id');
    }
}
