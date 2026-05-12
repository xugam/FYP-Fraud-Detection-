<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskLog extends Model
{
    protected $table = 'risk_logs';

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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
