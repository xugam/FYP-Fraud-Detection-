<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrozenAccount extends Model
{
    protected $fillable = [
        'user_id',
        'reason',
        'frozen_by',
        'frozen_at',
        'is_active'
    ];
    protected $casts = [
        'frozen_at' => 'datetime',
        'unfrozen_at' => 'datetime'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
