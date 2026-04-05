<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use App\Services\FraudDetectionService;
use App\Models\RiskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FraudDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public User $sender,
        public float $balanceAtTime,
    ) {}

    public function handle(FraudDetectionService $fraudService): void
    {
        $fraud = $fraudService->analyze($this->sender, $this->transaction->amount, $this->balanceAtTime);

        RiskLog::create([
            'transaction_id' => $this->transaction->id,
            'user_id'        => $this->sender->id,
            'score'          => $fraud['score'],
            'reasons'        => $fraud['reasons'],
            'action_taken'   => $fraud['action'],
        ]);
    }
}
