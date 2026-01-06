<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    // Quan hệ
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'category_id');
    }

    // Scopes
    public function scopeOfUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    // Accessors hỗ trợ nếu muốn dùng trong resource
    public function getOccurredAtIsoAttribute(): ?string
    {
        return $this->occurred_at?->toIso8601String();
    }

    public function getOccurredAtLocalAttribute(): ?string
    {
        $tz = config('app.timezone', 'Asia/Ho_Chi_Minh');
        return $this->occurred_at
            ? $this->occurred_at->copy()->setTimezone($tz)->format('Y-m-d H:i:s')
            : null;
    }

    public function getOccurredAtDisplayAttribute(): ?string
    {
        return $this->occurred_at
            ? $this->occurred_at->format('Y-m-d H:i:s')
            : null;
    }
}
