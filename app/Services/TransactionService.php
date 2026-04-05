<?php

namespace App\Services;

use App\Models\FrozenAccount;
use App\Models\RiskLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function __construct(
        private FraudDetectionService $fraudService,
        private AuditService $auditService,
    ) {}

    public function transfer(User $sender, int $receiverId, float $amount): Transaction
    {
        return DB::transaction(function () use ($sender, $receiverId, $amount) {

            // Lock both wallets to prevent race conditions (order by ID to avoid deadlocks)
            $walletIds  = collect([$sender->id, $receiverId])->sort()->values();
            $wallets    = Wallet::whereIn('user_id', $walletIds)->lockForUpdate()->get()->keyBy('user_id');

            $senderWallet   = $wallets->get($sender->id);
            $receiverWallet = $wallets->get($receiverId);

            // Validations
            if (!$receiverWallet) {
                throw new \Exception('Receiver wallet not found.');
            }

            if ($sender->isFrozen()) {
                throw new \Exception('Your account is frozen. Transactions are blocked.');
            }

            if ($senderWallet->balance < $amount) {
                throw new \Exception('Insufficient balance.');
            }

            // Create transaction record (pending)
            $transaction = Transaction::create([
                'sender_id'   => $sender->id,
                'receiver_id' => $receiverId,
                'amount'      => $amount,
                'status'      => 'pending',
                'risk_score'  => 0,
            ]);

            // Run fraud detection
            $fraud = $this->fraudService->analyze($sender, $amount, $senderWallet->balance);

            // Update transaction with score
            $transaction->update(['risk_score' => $fraud['score']]);

            // Log risk
            RiskLog::create([
                'transaction_id' => $transaction->id,
                'user_id'        => $sender->id,
                'score'          => $fraud['score'],
                'reasons'        => $fraud['reasons'],
                'action_taken'   => $fraud['action'],
            ]);

            // High risk → freeze account and fail transaction
            if ($fraud['action'] === 'frozen') {
                FrozenAccount::create([
                    'user_id'   => $sender->id,
                    'reason'    => 'Automatic freeze: high fraud score (' . $fraud['score'] . ')',
                    'frozen_at' => now(),
                    'is_active' => true,
                ]);

                $transaction->update(['status' => 'failed', 'failure_reason' => 'Account frozen due to high risk score.']);

                $this->auditService->log('account_frozen', $sender->id, [
                    'score'   => $fraud['score'],
                    'reasons' => $fraud['reasons'],
                ]);

                throw new \Exception('Transaction blocked. Your account has been frozen due to suspicious activity.');
            }

            // Medium risk → flag but allow
            if ($fraud['action'] === 'flagged') {
                $transaction->update(['status' => 'flagged']);
            } else {
                $transaction->update(['status' => 'success']);
            }

            // Move funds
            $senderWallet->decrement('balance', $amount);
            $receiverWallet->increment('balance', $amount);

            $this->auditService->log('transfer', $sender->id, [
                'to'     => $receiverId,
                'amount' => $amount,
                'status' => $transaction->status,
                'score'  => $fraud['score'],
            ]);

            return $transaction->fresh();
        });
    }
}
