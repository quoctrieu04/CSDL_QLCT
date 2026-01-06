<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Alert.php
class Alert extends Model
{
    protected $fillable = [
        'user_id','scope','code','level','message','context','hash','status','created_at'
    ];

    protected $casts = [
        'context' => 'array',   // rất quan trọng để $alert->context là mảng
    ];
}


