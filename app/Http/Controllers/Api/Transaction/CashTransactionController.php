<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\User;
use App\Http\Controllers\Communication\SMSController;

class CashTransactionController extends Controller
{
    /**
     * GET /api/transactions/cash
     * List cash transactions (deposits & withdrawals).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Transaction::with(['branch', 'recordedBy'])
            ->whereIn('transaction_type', ['cash_deposit', 'cash_withdrawal', 'fund_transfer']);

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        $transactions = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $transactions->map(function ($tx) {
                return [
                    'id' => (string) $tx->id,
                    'code' => $tx->transaction_code,
                    'type' => strtoupper($tx->transaction_type),
                    'branch_name' => $tx->branch->name ?? 'Unknown',
                    'amount_tzs' => (float) $tx->amount,
                    'payment_method' => strtoupper($tx->payment_method ?? 'CASH'),
                    'recorded_by' => $tx->recordedBy->name ?? 'System',
                    'status' => strtoupper($tx->status),
                    'date' => $tx->created_at->format('d M Y, h:i A'),
                ];
            }),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * POST /api/transactions/cash/deposit
     */
    public function storeDeposit(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = $request->branch_id;
            $branch = Branch::find($branchId);

            $transactionCode = $this->generateTransactionCode('CD');

            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'branch_id' => $branchId,
                'transaction_type' => 'cash_deposit',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Cash deposit via mobile app',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'recorded_by' => $user->id,
                'status' => 'completed',
            ]);

            // Update branch cash balance
            $cashBalance = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'cash')->lockForUpdate()->first();
            if ($cashBalance) {
                $cashBalance->amount += $request->amount;
                $cashBalance->save();
            } else {
                BranchBalance::create([
                    'branch_id' => $branchId,
                    'balance_type' => 'cash',
                    'amount' => $request->amount,
                    'currency' => 'TZS',
                ]);
            }

            DB::commit();

            // Trigger SMS (silently fail if SMS service is down)
            try { $this->sendDepositNotifications($transaction, $branch, $user); } catch (\Exception $e) {}

            return response()->json([
                'status' => true,
                'message' => "Cash deposit of TZS " . number_format($request->amount) . " recorded successfully!",
                'data' => ['transaction_code' => $transactionCode]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/transactions/cash/withdraw
     */
    public function storeWithdraw(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = $request->branch_id;
            $branch = Branch::find($branchId);

            $cashBalance = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'cash')->lockForUpdate()->first();

            if (!$cashBalance || $cashBalance->amount < $request->amount) {
                throw new \Exception('Insufficient cash balance. Available: ' . number_format($cashBalance->amount ?? 0) . ' TZS');
            }

            $transactionCode = $this->generateTransactionCode('CW');

            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'branch_id' => $branchId,
                'transaction_type' => 'cash_withdrawal',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Cash withdrawal via mobile app',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'recorded_by' => $user->id,
                'status' => 'completed',
            ]);

            $cashBalance->amount -= $request->amount;
            $cashBalance->save();

            DB::commit();

            try { $this->sendWithdrawalNotifications($transaction, $branch, $user); } catch (\Exception $e) {}

            return response()->json([
                'status' => true,
                'message' => "Cash withdrawal of TZS " . number_format($request->amount) . " recorded successfully!",
                'data' => ['transaction_code' => $transactionCode]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/transactions/cash/transfers
     */
    public function transfers(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Transaction::with(['branch', 'recordedBy'])->where('transaction_type', 'fund_transfer');

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        $transfers = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $transfers->map(function ($tx) {
                return [
                    'id' => (string) $tx->id,
                    'code' => $tx->transaction_code,
                    'from_branch_name' => $tx->branch->name ?? 'Unknown',
                    'amount_tzs' => (float) $tx->amount,
                    'status' => strtoupper($tx->status),
                    'date' => $tx->created_at->format('d M Y, h:i A'),
                ];
            }),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
            ]
        ]);
    }

    /**
     * POST /api/transactions/cash/transfers
     */
    public function storeTransfer(Request $request)
    {
        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $fromBranch = Branch::find($request->from_branch_id);
            $toBranch = Branch::find($request->to_branch_id);

            $fromBalance = BranchBalance::where('branch_id', $request->from_branch_id)->where('balance_type', 'cash')->lockForUpdate()->first();

            if (!$fromBalance || $fromBalance->amount < $request->amount) {
                throw new \Exception('Insufficient cash balance in source branch.');
            }

            $transactionCode = $this->generateTransactionCode('FT');

            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'branch_id' => $request->from_branch_id,
                'transaction_type' => 'fund_transfer',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Fund transfer to ' . $toBranch->name,
                'recorded_by' => $user->id,
                'status' => 'completed',
                'related_transaction' => $request->to_branch_id,
            ]);

            $fromBalance->amount -= $request->amount;
            $fromBalance->save();

            $toBalance = BranchBalance::where('branch_id', $request->to_branch_id)->where('balance_type', 'cash')->lockForUpdate()->first();
            if ($toBalance) {
                $toBalance->amount += $request->amount;
                $toBalance->save();
            } else {
                BranchBalance::create([
                    'branch_id' => $request->to_branch_id,
                    'balance_type' => 'cash',
                    'amount' => $request->amount,
                    'currency' => 'TZS',
                ]);
            }

            DB::commit();

            try { $this->sendTransferNotifications($transaction, $fromBranch, $toBranch, $user); } catch (\Exception $e) {}

            return response()->json([
                'status' => true,
                'message' => "Fund transfer of TZS " . number_format($request->amount) . " completed successfully!",
                'data' => ['transaction_code' => $transactionCode]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ============================================
    // HELPERS
    // ============================================
    private function generateTransactionCode($prefix)
    {
        $prefix = $prefix . date('Ymd');
        $lastTransaction = Transaction::where('transaction_code', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $newNumber = $lastTransaction ? str_pad(intval(substr($lastTransaction->transaction_code, -4)) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        return $prefix . '-' . $newNumber;
    }

    // ============================================
    // SMS NOTIFICATIONS (Copied from web controller)
    // ============================================
    private function sendDepositNotifications($transaction, $branch, $user)
    {
        try {
            $directors = User::where('role', 'director')->get();
            $branchManager = User::where('role', 'branch_manager')->where('assigned_branch', $transaction->branch_id)->first();
            $smsController = app(SMSController::class);

            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) $smsController->sendSms($phone, "Cash Deposit Alert: TZS " . number_format($transaction->amount) . " deposited at " . ($branch->name ?? 'N/A') . ". Ref: " . $transaction->transaction_code, 'NGEMBA MINE');
            }

            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) $smsController->sendSms($phone, "Cash Deposit: TZS " . number_format($transaction->amount) . " at " . ($branch->name ?? 'N/A') . ". Ref: " . $transaction->transaction_code, 'NGEMBA MINE');
                }
            }
        } catch (\Exception $e) {}
    }

    private function sendWithdrawalNotifications($transaction, $branch, $user)
    {
        try {
            $directors = User::where('role', 'director')->get();
            $branchManager = User::where('role', 'branch_manager')->where('assigned_branch', $transaction->branch_id)->first();
            $smsController = app(SMSController::class);

            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) $smsController->sendSms($phone, "Cash Withdrawal Alert: TZS " . number_format($transaction->amount) . " withdrawn from " . ($branch->name ?? 'N/A') . ". Ref: " . $transaction->transaction_code, 'NGEMBA MINE');
            }

            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) $smsController->sendSms($phone, "Cash Withdrawal: TZS " . number_format($transaction->amount) . " from " . ($branch->name ?? 'N/A') . ". Ref: " . $transaction->transaction_code, 'NGEMBA MINE');
                }
            }
        } catch (\Exception $e) {}
    }

    private function sendTransferNotifications($transaction, $fromBranch, $toBranch, $user)
    {
        try {
            $directors = User::where('role', 'director')->get();
            $smsController = app(SMSController::class);

            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) $smsController->sendSms($phone, "Transfer: TZS " . number_format($transaction->amount) . " from " . ($fromBranch->name ?? 'N/A') . " to " . ($toBranch->name ?? 'N/A') . ". Ref: " . $transaction->transaction_code, 'NGEMBA MINE');
                }
            }
        } catch (\Exception $e) {}
    }
}