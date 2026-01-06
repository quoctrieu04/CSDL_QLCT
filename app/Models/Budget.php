<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\OutCategory;
use App\Models\User;
use App\Models\BudgetDetail;
use App\Models\BudgetInTransaction;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'amount',
    ];

    public $timestamps = true;

    /**
     * 🔗 Liên kết tới danh mục chi tiêu (OutCategory)
     */
    public function category()
    {
        return $this->belongsTo(OutCategory::class, 'category_id');
    }

    /**
     * 👤 Liên kết tới người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 📅 Liên kết tới bảng chi tiết ngân sách theo tháng/năm
     */
    public function details()
    {
        return $this->hasMany(BudgetDetail::class, 'budget_id');
    }

    /**
     * 💰 Liên kết tới các giao dịch ngân sách (chi tiêu / phân bổ)
     */
    public function transactions()
    {
        return $this->hasMany(BudgetInTransaction::class, 'budget_id');
    }

    /**
     * 💡 Tổng chi tiêu thực tế (operation = -1)
     */
    public function getTotalSpentAttribute()
    {
        return $this->transactions()
                    ->where('operation', -1)
                    ->sum('amount');
    }

    /**
     * 💡 Tổng tiền đã phân bổ (operation = 1)
     */
    public function getTotalAllocatedAttribute()
    {
        return $this->transactions()
                    ->where('operation', 1)
                    ->sum('amount');
    }

    /**
     * 💡 Số dư còn lại = tổng phân bổ - tổng chi tiêu
     */
    public function getRemainingAttribute()
    {
        return $this->total_allocated - $this->total_spent;
    }

    /**
     * 🔄 Giảm ngân sách khi có chi tiêu
     */
    public function decreaseAmount($value)
    {
        $this->amount = max(0, $this->amount - $value);
        $this->save();
    }

    /**
     * 🔄 Tăng ngân sách khi phân bổ
     */
    public function increaseAmount($value)
    {
        $this->amount += $value;
        $this->save();
    }
}
