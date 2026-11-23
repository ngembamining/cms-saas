<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\User;
use App\Http\Controllers\Communication\SMSController;

class CashTransactionController extends Controller
{
    /**
     * Display a listing of cash transactions.
     */
    public function index()
    {
        $user = Auth::user();
        
        $transactions = Transaction::with(['branch', 'user'])
            ->whereIn('transaction_type', ['cash_deposit', 'cash_withdrawal', 'fund_transfer'])
            ->when($user->role !== 'director', function ($query) use ($user) {
                return $query->where('branch_id', $user->assigned_branch);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $totalDeposits = Transaction::where('transaction_type', 'cash_deposit')
            ->when($user->role !== 'director', function ($query) use ($user) {
                return $query->where('branch_id', $user->assigned_branch);
            })
            ->sum('amount');
            
        $totalWithdrawals = Transaction::where('transaction_type', 'cash_withdrawal')
            ->when($user->role !== 'director', function ($query) use ($user) {
                return $query->where('branch_id', $user->assigned_branch);
            })
            ->sum('amount');
        
        return view('transactions.cash.index', compact(
            'transactions',
            'totalDeposits',
            'totalWithdrawals'
        ));
    }

    /**
     * Show deposit form.
     */
    public function depositForm()
    {
        $user = Auth::user();
        
        if ($user->role === 'director') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }
        
        return view('transactions.cash.deposit', compact('branches'));
    }

    /**
     * Store a cash deposit.
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

            $user = Auth::user();
            $branchId = $request->branch_id;
            $branch = Branch::find($branchId);

            // Generate transaction code
            $transactionCode = $this->generateTransactionCode('CD');

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'branch_id' => $branchId,
                'transaction_type' => 'cash_deposit',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Cash deposit',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'recorded_by' => $user->id,
                'status' => 'completed',
            ]);

            // Update branch cash balance
            $cashBalance = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->first();

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

            // ============================================
            // ✅ SEND SMS NOTIFICATIONS
            // ============================================
            $this->sendDepositNotifications($transaction, $branch, $user);

            return redirect()->route('transactions.cash.index')
                ->with('success', sprintf(
                    '✅ Cash deposit of TZS %s recorded successfully! Transaction: %s',
                    number_format($request->amount),
                    $transactionCode
                ));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to record deposit: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show withdrawal form.
     */
    public function withdrawForm()
    {
        $user = Auth::user();
        
        if ($user->role === 'director') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }
        
        return view('transactions.cash.withdraw', compact('branches'));
    }

    /**
     * Store a cash withdrawal.
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

            $user = Auth::user();
            $branchId = $request->branch_id;
            $branch = Branch::find($branchId);

            // Check if branch has enough cash
            $cashBalance = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->first();

            if (!$cashBalance || $cashBalance->amount < $request->amount) {
                throw new \Exception('Insufficient cash balance. Available: ' . number_format($cashBalance->amount ?? 0) . ' TZS');
            }

            // Generate transaction code
            $transactionCode = $this->generateTransactionCode('CW');

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'branch_id' => $branchId,
                'transaction_type' => 'cash_withdrawal',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Cash withdrawal',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'recorded_by' => $user->id,
                'status' => 'completed',
            ]);

            // Update branch cash balance
            if ($cashBalance) {
                $cashBalance->amount -= $request->amount;
                $cashBalance->save();
            }

            DB::commit();

            // ============================================
            // ✅ SEND SMS NOTIFICATIONS
            // ============================================
            $this->sendWithdrawalNotifications($transaction, $branch, $user);

            return redirect()->route('transactions.cash.index')
                ->with('success', sprintf(
                    '✅ Cash withdrawal of TZS %s recorded successfully! Transaction: %s',
                    number_format($request->amount),
                    $transactionCode
                ));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to record withdrawal: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display transfers.
     */
    public function transfers()
    {
        $user = Auth::user();
        
        $transfers = Transaction::with(['branch', 'user'])
            ->where('transaction_type', 'fund_transfer')
            ->when($user->role !== 'director', function ($query) use ($user) {
                return $query->where('branch_id', $user->assigned_branch);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('transactions.cash.transfers', compact('transfers'));
    }

    /**
     * Store a fund transfer.
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

            $user = Auth::user();
            $fromBranch = Branch::find($request->from_branch_id);
            $toBranch = Branch::find($request->to_branch_id);

            // Check if source branch has enough cash
            $fromBalance = BranchBalance::where('branch_id', $request->from_branch_id)
                ->where('balance_type', 'cash')
                ->first();

            if (!$fromBalance || $fromBalance->amount < $request->amount) {
                throw new \Exception('Insufficient cash balance in source branch');
            }

            // Generate transaction code
            $transactionCode = $this->generateTransactionCode('FT');

            // Create transaction
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

            // Deduct from source branch
            $fromBalance->amount -= $request->amount;
            $fromBalance->save();

            // Add to destination branch
            $toBalance = BranchBalance::where('branch_id', $request->to_branch_id)
                ->where('balance_type', 'cash')
                ->first();

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

            // ============================================
            // ✅ SEND SMS NOTIFICATIONS
            // ============================================
            $this->sendTransferNotifications($transaction, $fromBranch, $toBranch, $user);

            return redirect()->route('transactions.cash.index')
                ->with('success', sprintf(
                    '✅ Fund transfer of TZS %s completed successfully! Transaction: %s',
                    number_format($request->amount),
                    $transactionCode
                ));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to transfer funds: ' . $e->getMessage())
                ->withInput();
        }
    }

    // ============================================
    // SMS NOTIFICATION METHODS
    // ============================================

    /**
     * Send deposit notifications
     */
    private function sendDepositNotifications($transaction, $branch, $user)
    {
        try {
            \Log::info('Starting SMS notifications for deposit: ' . $transaction->transaction_code);
            
            // Get all directors
            $directors = User::where('role', 'director')->get();
            
            // Get branch manager for this branch
            $branchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $transaction->branch_id)
                ->first();

            // Get the SMS controller instance
            $smsController = app(SMSController::class);

            // 1. SMS TO BRANCH MANAGER - Deposit alert
            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) {
                    $message = "Cash Deposit Alert: TZS " . number_format($transaction->amount) . " deposited at " . ($branch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code . ". Recorded by: " . ($user->full_name ?? $user->username);
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('SMS sent to manager for deposit: ' . $phone);
                }
            }

            // 2. SMS TO DIRECTORS - Deposit report
            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) {
                        $message = "Cash Deposit Report: TZS " . number_format($transaction->amount) . " deposited at " . ($branch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code . ". Date: " . $transaction->created_at->format('d/m/Y H:i');
                        $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                        \Log::info('SMS sent to director for deposit: ' . $phone);
                    }
                }
            }

            \Log::info('All SMS notifications sent for deposit: ' . $transaction->transaction_code);

        } catch (\Exception $e) {
            \Log::error('Failed to send SMS notifications for deposit: ' . $e->getMessage());
        }
    }

    /**
     * Send withdrawal notifications
     */
    private function sendWithdrawalNotifications($transaction, $branch, $user)
    {
        try {
            \Log::info('Starting SMS notifications for withdrawal: ' . $transaction->transaction_code);
            
            // Get all directors
            $directors = User::where('role', 'director')->get();
            
            // Get branch manager for this branch
            $branchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $transaction->branch_id)
                ->first();

            // Get the SMS controller instance
            $smsController = app(SMSController::class);

            // 1. SMS TO BRANCH MANAGER - Withdrawal alert
            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) {
                    $message = "Cash Withdrawal Alert: TZS " . number_format($transaction->amount) . " withdrawn from " . ($branch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code . ". Recorded by: " . ($user->full_name ?? $user->username);
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('SMS sent to manager for withdrawal: ' . $phone);
                }
            }

            // 2. SMS TO DIRECTORS - Withdrawal report
            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) {
                        $message = "Cash Withdrawal Report: TZS " . number_format($transaction->amount) . " withdrawn from " . ($branch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code . ". Date: " . $transaction->created_at->format('d/m/Y H:i');
                        $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                        \Log::info('SMS sent to director for withdrawal: ' . $phone);
                    }
                }
            }

            \Log::info('All SMS notifications sent for withdrawal: ' . $transaction->transaction_code);

        } catch (\Exception $e) {
            \Log::error('Failed to send SMS notifications for withdrawal: ' . $e->getMessage());
        }
    }

    /**
     * Send fund transfer notifications
     */
    private function sendTransferNotifications($transaction, $fromBranch, $toBranch, $user)
    {
        try {
            \Log::info('Starting SMS notifications for transfer: ' . $transaction->transaction_code);
            
            // Get all directors
            $directors = User::where('role', 'director')->get();
            
            // Get branch managers for both branches
            $fromBranchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $fromBranch->id)
                ->first();
            
            $toBranchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $toBranch->id)
                ->first();

            // Get the SMS controller instance
            $smsController = app(SMSController::class);

            // 1. SMS TO FROM BRANCH MANAGER - Transfer out
            if ($fromBranchManager && $fromBranchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($fromBranchManager->phone_number);
                if ($phone) {
                    $message = "Fund Transfer Out: TZS " . number_format($transaction->amount) . " transferred from " . ($fromBranch->name ?? 'N/A') . " to " . ($toBranch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code;
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('SMS sent to from branch manager: ' . $phone);
                }
            }

            // 2. SMS TO TO BRANCH MANAGER - Transfer in
            if ($toBranchManager && $toBranchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($toBranchManager->phone_number);
                if ($phone) {
                    $message = "Fund Transfer In: TZS " . number_format($transaction->amount) . " received from " . ($fromBranch->name ?? 'N/A') . " to " . ($toBranch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code;
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('SMS sent to to branch manager: ' . $phone);
                }
            }

            // 3. SMS TO DIRECTORS - Transfer report
            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) {
                        $message = "Fund Transfer Report: TZS " . number_format($transaction->amount) . " transferred from " . ($fromBranch->name ?? 'N/A') . " to " . ($toBranch->name ?? 'N/A') . ". Transaction: " . $transaction->transaction_code . ". Date: " . $transaction->created_at->format('d/m/Y H:i');
                        $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                        \Log::info('SMS sent to director for transfer: ' . $phone);
                    }
                }
            }

            \Log::info('All SMS notifications sent for transfer: ' . $transaction->transaction_code);

        } catch (\Exception $e) {
            \Log::error('Failed to send SMS notifications for transfer: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique transaction code.
     */
    private function generateTransactionCode($prefix)
    {
        $prefix = $prefix . date('Ymd');
        $lastTransaction = Transaction::where('transaction_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . '-' . $newNumber;
    }
}