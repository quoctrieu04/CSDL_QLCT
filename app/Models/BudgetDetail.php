<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetDetail extends Model
{
    use HasFactory;

    protected $table = 'budget_details';

    protected $fillable = [
        'budget_id',
        'user_id',
        'amount',
        'month',
        'year',
    ];

    public $timestamps = true;

    /**
     * 🔗 Quan hệ: BudgetDetail thuộc về một Budget
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * 👤 Quan hệ: BudgetDetail thuộc về User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 💡 Scope: lọc theo tháng & năm hiện tại
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month', date('n'))->where('year', date('Y'));
    }

    /**
     * 📅 Scope: lọc theo tháng & năm cụ thể
     */
    public function scopeForMonthYear($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
