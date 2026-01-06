<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutCategory extends Model
{
    protected $fillable = ['user_id', 'title', 'is_delete'];
    protected $casts = ['is_delete' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Quan hệ 1-1 tới Budget
    public function budget()
    {
        return $this->hasOne(Budget::class, 'category_id');
    }
}
