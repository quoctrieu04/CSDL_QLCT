<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\OutCategory;
use App\Models\Budget;

class OutInvoice extends Model
{
    use HasFactory;

    protected $table = 'out_invoices';

    protected $fillable = [
        'user_id',
        'outcat_id',
        'banktrans_id',
        'amount',
        'month',      // ✅ thêm dòng này
        'year',       // ✅ thêm dòng này
        'doc_type',
        'doctrans_id',
        'content',
        'occurred_at',
    ];

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'banktrans_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(OutCategory::class, 'outcat_id');
    }

    // ✅ Liên kết hóa đơn với ngân sách
    public function budget()
    {
        return $this->hasOne(Budget::class, 'category_id', 'outcat_id')
            ->where('user_id', $this->user_id);
    }
}
