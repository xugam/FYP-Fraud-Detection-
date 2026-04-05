<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormRequest\TransferRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function transfer(TransferRequest $request)
    {
        try {
            $transaction = $this->transactionService->transfer(
                $request->user(),
                $request->receiver_id,
                $request->amount,
            );

            return response()->json([
                'message'     => 'Transfer completed.',
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function history(Request $request)
    {
        $txs = Transaction::where('sender_id', $request->user()->id)
            ->orWhere('receiver_id', $request->user()->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($txs);
    }
}
