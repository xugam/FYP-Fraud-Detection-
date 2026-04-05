<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getBalance(User $user): array
    {
        $wallet = $user->wallet;
        return [
            'balance'    => $wallet->balance,
            'updated_at' => $wallet->updated_at,
        ];
    }

    public function deposit(User $user, float $amount): Wallet
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $wallet->increment('balance', $amount);
            return $wallet->fresh();
        });
    }
}
