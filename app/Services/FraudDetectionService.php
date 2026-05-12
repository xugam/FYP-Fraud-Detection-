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

        // Rule 3: Frequency — FIXED thresholds (was too lenient)
        $recentCount = Transaction::where('sender_id', $sender->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {          // ← was 5, now 3 triggers high risk
            $score   += 50;               // ← was 25, now 50 so 3 txns = freeze
            $reasons[] = "High frequency: {$recentCount} transactions in 10 minutes";
        } elseif ($recentCount >= 2) {    // ← was 3, now 2 triggers medium
            $score   += 25;
            $reasons[] = "Moderate frequency: {$recentCount} transactions in 10 minutes";
        }

        // Rule 4: Total sent in last hour > 50,000
        $hourlyTotal = Transaction::where('sender_id', $sender->id)
            ->whereIn('status', ['success', 'flagged'])
            ->where('created_at', '>=', now()->subHour())
            ->sum('amount');

        if (($hourlyTotal + $amount) > 50000) {
            $score   += 25;
            $reasons[] = 'Hourly transfer limit approached or exceeded';
        }

        // Rule 5: First-time transfer > 1,000
        $priorTxCount = Transaction::where('sender_id', $sender->id)
            ->where('status', 'success')
            ->count();

        if ($priorTxCount === 0 && $amount > 1000) {
            $score   += 15;
            $reasons[] = 'Large amount from account with no prior transactions';
        }

        $score = min($score, 100);

        return [
            'score'   => $score,
            'reasons' => $reasons,
            'action'  => $this->determineAction($score),
        ];
    }

    private function determineAction(int $score): string
    {
        if ($score >= self::HIGH_RISK) return 'frozen';
        if ($score >= self::LOW_RISK)  return 'flagged';
        return 'allowed';
    }
}
