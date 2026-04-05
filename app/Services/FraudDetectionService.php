<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FraudDetectionService
{
    const LOW_RISK    = 40;
    const HIGH_RISK   = 70;

    public function analyze(User $sender, float $amount, float $senderBalance): array
    {
        $score   = 0;
        $reasons = [];

        // Rule 1: Amount > 80% of balance
        if ($senderBalance > 0 && ($amount / $senderBalance) > 0.8) {
            $score   += 30;
            $reasons[] = 'Amount exceeds 80% of sender balance';
        }

        // Rule 2: Amount > 10,000 (large transaction)
        if ($amount > 10000) {
            $score   += 20;
            $reasons[] = 'High absolute transaction amount';
        }

        // Rule 3: More than 5 transactions in the last 10 minutes
        $recentCount = Transaction::where('sender_id', $sender->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 5) {
            $score   += 25;
            $reasons[] = "High frequency: {$recentCount} transactions in 10 minutes";
        } elseif ($recentCount >= 3) {
            $score   += 10;
            $reasons[] = "Moderate frequency: {$recentCount} transactions in 10 minutes";
        }

        // Rule 4: Total sent in last hour > 50,000
        $hourlyTotal = Transaction::where('sender_id', $sender->id)
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subHour())
            ->sum('amount');

        if (($hourlyTotal + $amount) > 50000) {
            $score   += 25;
            $reasons[] = 'Hourly transfer limit approached or exceeded';
        }

        // Rule 5: First-time transfer (no prior successful transactions)
        $priorTxCount = Transaction::where('sender_id', $sender->id)
            ->where('status', 'success')
            ->count();

        if ($priorTxCount === 0 && $amount > 1000) {
            $score   += 15;
            $reasons[] = 'Large amount from an account with no prior transactions';
        }

        $score = min($score, 100); // cap at 100

        return [
            'score'       => $score,
            'reasons'     => $reasons,
            'action'      => $this->determineAction($score),
        ];
    }

    private function determineAction(int $score): string
    {
        if ($score >= self::HIGH_RISK) return 'frozen';
        if ($score >= self::LOW_RISK)  return 'flagged';
        return 'allowed';
    }
}
