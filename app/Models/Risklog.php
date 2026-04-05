<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'score',
        'reasons',
        'action_taken'
    ];
    protected $casts = [
        'reasons' => 'array'
    ];
}
