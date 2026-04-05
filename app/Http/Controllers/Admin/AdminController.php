<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrozenAccount;
use App\Models\RiskLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function users()
    {
        return response()->json(User::with('wallet')->paginate(20));
    }

    public function transactions()
    {
        return response()->json(
            Transaction::with(['sender:id,name', 'receiver:id,name'])->latest()->paginate(20)
        );
    }

    public function flaggedTransactions()
    {
        return response()->json(
            Transaction::where('status', 'flagged')
                ->with(['sender:id,name', 'receiver:id,name', 'riskLog'])
                ->latest()
                ->paginate(20)
        );
    }

    public function riskLogs()
    {
        return response()->json(RiskLog::with('transaction')->latest()->paginate(20));
    }

    public function analytics()
    {
        return response()->json([
            'total_users'           => User::count(),
            'total_transactions'    => Transaction::count(),
            'flagged_transactions'  => Transaction::where('status', 'flagged')->count(),
            'frozen_accounts'       => FrozenAccount::where('is_active', true)->count(),
            'total_volume'          => Transaction::where('status', 'success')->sum('amount'),
        ]);
    }

    public function freezeAccount(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($user->isFrozen()) {
            return response()->json(['message' => 'Account is already frozen.'], 409);
        }

        FrozenAccount::create([
            'user_id'   => $user->id,
            'reason'    => $request->reason,
            'frozen_by' => $request->user()->id,
            'frozen_at' => now(),
            'is_active' => true,
        ]);

        $this->auditService->log('manual_freeze', $request->user()->id, [
            'target_user' => $user->id,
            'reason'      => $request->reason,
        ]);

        return response()->json(['message' => "User {$user->name} has been frozen."]);
    }

    public function unfreezeAccount(Request $request, User $user)
    {
        $frozen = FrozenAccount::where('user_id', $user->id)->where('is_active', true)->first();

        if (!$frozen) {
            return response()->json(['message' => 'Account is not frozen.'], 404);
        }

        $frozen->update(['is_active' => false, 'unfrozen_at' => now()]);

        $this->auditService->log('manual_unfreeze', $request->user()->id, [
            'target_user' => $user->id,
        ]);

        return response()->json(['message' => "User {$user->name} has been unfrozen."]);
    }
}
