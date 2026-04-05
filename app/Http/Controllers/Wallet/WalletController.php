<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormRequest\DepositRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function balance(Request $request)
    {
        return response()->json($this->walletService->getBalance($request->user()));
    }

    public function deposit(DepositRequest $request)
    {
        $wallet = $this->walletService->deposit($request->user(), $request->amount);

        return response()->json([
            'message' => 'Deposit successful.',
            'wallet'  => $wallet,
        ]);
    }
}
