<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\BranchBalance;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Http\Controllers\Communication\SMSController;

class GoldPurchaseController extends Controller
{
    protected $dividerConstant = 12;
    protected $defaultMarkupRate = 10;

    // PHP page allows: cash, bank, mobile
    // DB enum allows: cash, bank_transfer, mobile_money, credit
    // -> We'll accept the DB enum values
    protected $allowedPaymentMethods = ['cash', 'bank_transfer', 'mobile_money'];

    public function index()
    {
        $user = Auth::user();

        $purchases = Transaction::with(['branch', 'user', 'supplier', 'items'])
            ->where('transaction_type', 'gold_purchase')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalPurchases = Transaction::where('transaction_type', 'gold_purchase')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->sum('amount');

        $totalWeight = TransactionItem::whereHas('transaction', function ($q) use ($user) {
            $q->where('transaction_type', 'gold_purchase')
              ->when($user->role !== 'director', fn($qq) => $qq->where('branch_id', $user->assigned_branch));
        })->sum('weight');

        $pendingCount = Transaction::where('transaction_type', 'gold_purchase')
            ->where('status', 'pending')
            ->when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
            ->count();

        return view('transactions.gold-purchases.index',
            compact('purchases', 'totalPurchases', 'totalWeight', 'pendingCount'));
    }

    public function create()
    {
        $user = Auth::user();

        $branches = $user->role === 'director'
            ? Branch::all()
            : Branch::where('id', $user->assigned_branch)->get();

        $suppliers = Supplier::when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
            ->orderBy('name')->get();

        $goldRate = ExchangeRate::where('effective_date', '<=', now())
            ->orderBy('effective_date', 'desc')->first();
        $currentGoldRate = $goldRate->gold_rate ?? 0;

        $branch = null;
        $branchCash = 0;
        $branchGold = 0;

        if ($user->assigned_branch) {
            $branch = Branch::find($user->assigned_branch);
            if ($branch) {
                $branchGold = (float) ($branch->gold_available ?? 0);
                $cash = BranchBalance::where('branch_id', $user->assigned_branch)
                    ->where('balance_type', 'cash')->first();
                $branchCash = $cash->amount ?? 0;
            }
        }

        $requestToken = (string) Str::uuid();

        // Recent prices for reference (matches PHP 'recent prices' concept)
        $recentPrices = TransactionItem::whereHas('transaction', function ($q) use ($user) {
            $q->where('transaction_type', 'gold_purchase')
              ->when($user->role !== 'director', fn($qq) => $qq->where('branch_id', $user->assigned_branch))
              ->where('created_at', '>=', now()->subDays(7));
        })
        ->select('purity', 'agreed_price', 'weight')
        ->orderByDesc('id')->limit(5)->get();

        $defaultMarkupRate = $this->defaultMarkupRate;
        $dividerConstant = $this->dividerConstant;
        $paymentMethods = $this->allowedPaymentMethods;

        return view('transactions.gold-purchases.create', compact(
            'branches', 'suppliers', 'currentGoldRate', 'branch',
            'branchCash', 'branchGold', 'user', 'requestToken',
            'recentPrices', 'defaultMarkupRate', 'dividerConstant', 'paymentMethods'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'         => 'required|exists:branches,id',
            'supplier_id'       => 'nullable|integer|min:0',   // 0 = walk-in
            'weights'           => 'required|array|min:1',
            'weights.*'         => 'required|numeric|min:0.01',
            'purities'          => 'required|array',
            'purities.*'        => 'required|numeric|min:0.01|max:100',
            'prices'            => 'required|array',
            'prices.*'          => 'required|numeric|min:0.01',
            'markup_rate'       => 'required|numeric|min:0|max:100',
            'last_digits'       => 'nullable|string|max:4',
            'payment_method'    => 'required|in:' . implode(',', $this->allowedPaymentMethods),
            'description'       => 'nullable|string|max:500',
            'request_token'     => 'nullable|string|max:64',
        ]);

        // Duplicate guard
        $requestToken = $request->input('request_token');
        if ($requestToken) {
            $tokens = session('processed_gold_purchase_tokens', []);
            if (in_array($requestToken, $tokens, true)) {
                return redirect()->route('transactions.gold.purchases.index')
                    ->with('success', 'This purchase was already recorded — duplicate submission ignored.');
            }
        }

        try {
            DB::beginTransaction();

            $user       = Auth::user();
            $branchId   = (int) $request->branch_id;
            $supplierId = (int) ($request->supplier_id ?? 0);
            $markupRate = (float) $request->markup_rate;

            $totalWeight = 0;
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($request->weights as $i => $w) {
                $weight = (float) $w;
                $purity = (float) ($request->purities[$i] ?? 0);
                $price  = (float) ($request->prices[$i] ?? 0);

                if ($weight <= 0)  throw new \RuntimeException("Item ".($i+1).": Invalid weight");
                if ($purity <= 0 || $purity > 100) throw new \RuntimeException("Item ".($i+1).": Invalid purity");
                if ($price  <= 0)  throw new \RuntimeException("Item ".($i+1).": Invalid price");

                // PHP formula: (weight * purity/100 * price) / 12 * (1 - markup/100)
                $itemAmount = ($weight * ($purity/100) * $price) / $this->dividerConstant
                            * (1 - ($markupRate / 100));

                $totalWeight += $weight;
                $totalAmount += $itemAmount;

                $itemsData[] = [
                    'weight' => $weight,
                    'purity' => $purity,
                    'price'  => $price,
                ];
            }

            if ($totalWeight <= 0) throw new \RuntimeException('No valid gold items entered');

            // Cash check
            $cashBalance = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')->first();

            if (!$cashBalance || $cashBalance->amount < $totalAmount) {
                $avail = number_format($cashBalance->amount ?? 0);
                $need  = number_format($totalAmount);
                throw new \RuntimeException("Insufficient cash balance. Available: TZS $avail, Required: TZS $need");
            }

            // Last-4-digit adjustment (matches PHP)
            $lastDigits = preg_replace('/[^0-9]/', '', (string) $request->input('last_digits', ''));
            if (strlen($lastDigits) === 4) {
                $base = floor($totalAmount / 10000) * 10000;
                $adj  = $base + (int) $lastDigits;
                if (abs($adj - $totalAmount) < 10000) {
                    $totalAmount = $adj;
                }
            }
            $totalAmount = round($totalAmount, 2);

            // DB duplicate guard (30s window)
            $dup = Transaction::where('transaction_type', 'gold_purchase')
                ->where('branch_id', $branchId)
                ->where('supplier_id', $supplierId ?: null)
                ->where('amount', $totalAmount)
                ->where('recorded_by', $user->id)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->exists();
            if ($dup) {
                DB::rollBack();
                return redirect()->route('transactions.gold.purchases.index')
                    ->with('success', 'This purchase was already recorded — duplicate submission ignored.');
            }

            $transactionCode = $this->generateTransactionCode();

            $transaction = Transaction::create([
                'transaction_code'  => $transactionCode,
                'branch_id'         => $branchId,
                'transaction_type'  => 'gold_purchase',
                'amount'            => $totalAmount,
                'supplier_id'       => $supplierId ?: null,
                'description'       => $request->description ?? 'Gold purchase from supplier',
                'payment_method'    => $request->payment_method,
                'payment_reference' => null,
                'recorded_by'       => $user->id,
                'status'            => 'completed',
            ]);

            foreach ($itemsData as $it) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'weight'         => $it['weight'],
                    'purity'         => $it['purity'],
                    'agreed_price'   => $it['price'],
                ]);
            }

            // ===== BALANCE UPDATES — match PHP exactly =====
            // Cash DOWN in branch_balances
            $cashBalance->amount = $cashBalance->amount - $totalAmount;
            $cashBalance->save();

            // Gold UP in branches.gold_available (this is what PHP writes to)
            $branch = Branch::find($branchId);
            $branch->gold_available = ($branch->gold_available ?? 0) + $totalWeight;
            $branch->save();

            // Mirror to branch_balances (gold) so both stay in sync
            $goldBal = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')->first();
            if ($goldBal) {
                $goldBal->amount = $goldBal->amount + $totalWeight;
                $goldBal->save();
            } else {
                BranchBalance::create([
                    'branch_id'    => $branchId,
                    'balance_type' => 'gold',
                    'amount'       => $totalWeight,
                ]);
            }

            DB::commit();

            // Token cleanup
            if ($requestToken) {
                $tokens[] = $requestToken;
                if (count($tokens) > 50) $tokens = array_slice($tokens, -50);
                session(['processed_gold_purchase_tokens' => $tokens]);
            }

            // SMS (matches PHP — sends thank-you to supplier, alert to manager, report to directors)
            $supplier = $supplierId ? Supplier::find($supplierId) : null;
            $this->sendPurchaseNotifications($transaction, $supplier, $branch, $totalWeight, $itemsData);

            return redirect()->route('transactions.gold.purchases.index')
                ->with('success', sprintf(
                    'Gold purchase recorded successfully! %sg purchased for TZS %s. Transaction ID: %s.',
                    number_format($totalWeight, 2),
                    number_format($totalAmount),
                    $transactionCode
                ));

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gold purchase failed: '.$e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to record gold purchase: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $purchase = Transaction::with(['branch', 'user', 'supplier', 'items'])
            ->where('transaction_type', 'gold_purchase')->findOrFail($id);

        $user = Auth::user();
        if ($user->role !== 'director' && $purchase->branch_id != $user->assigned_branch) {
            abort(403);
        }
        return view('transactions.gold-purchases.show', compact('purchase'));
    }

    public function edit($id)
    {
        $purchase = Transaction::with('items')
            ->where('transaction_type', 'gold_purchase')->findOrFail($id);
        if ($purchase->status !== 'pending') {
            return redirect()->route('transactions.gold.purchases.index')
                ->with('error', 'This purchase cannot be edited.');
        }
        $user = Auth::user();
        if ($user->role !== 'director' && $purchase->branch_id != $user->assigned_branch) abort(403);

        $branches = Branch::all();
        $suppliers = Supplier::all();
        $defaultMarkupRate = $this->defaultMarkupRate;
        $dividerConstant   = $this->dividerConstant;
        $paymentMethods    = $this->allowedPaymentMethods;

        return view('transactions.gold-purchases.edit',
            compact('purchase', 'branches', 'suppliers', 'defaultMarkupRate', 'dividerConstant', 'paymentMethods'));
    }

    public function update(Request $request, $id)
    {
        $purchase = Transaction::with('items')
            ->where('transaction_type', 'gold_purchase')->findOrFail($id);
        if ($purchase->status !== 'pending') {
            return redirect()->route('transactions.gold.purchases.index')->with('error', 'Cannot edit.');
        }

        $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'supplier_id' => 'nullable|integer|min:0',
            'weights'     => 'required|array|min:1',
            'weights.*'   => 'required|numeric|min:0.01',
            'purities'    => 'required|array',
            'purities.*'  => 'required|numeric|min:0.01|max:100',
            'prices'      => 'required|array',
            'prices.*'    => 'required|numeric|min:0.01',
            'markup_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();
            $markupRate = (float) $request->markup_rate;
            $totalWeight = 0; $totalAmount = 0;

            foreach ($request->weights as $i => $w) {
                $weight = (float) $w;
                $purity = (float) ($request->purities[$i] ?? 0);
                $price  = (float) ($request->prices[$i] ?? 0);

                $totalWeight += $weight;
                $totalAmount += ($weight * ($purity/100) * $price) / $this->dividerConstant
                              * (1 - ($markupRate / 100));
            }
            $totalAmount = round($totalAmount, 2);

            $purchase->update([
                'branch_id'   => $request->branch_id,
                'supplier_id' => ((int)($request->supplier_id ?? 0)) ?: null,
                'amount'      => $totalAmount,
                'description' => $request->description ?? 'Gold purchase',
            ]);

            $purchase->items()->delete();
            foreach ($request->weights as $i => $w) {
                TransactionItem::create([
                    'transaction_id' => $purchase->id,
                    'weight'         => $w,
                    'purity'         => $request->purities[$i] ?? 0,
                    'agreed_price'   => $request->prices[$i] ?? 0,
                ]);
            }
            DB::commit();
            return redirect()->route('transactions.gold.purchases.index')
                ->with('success', 'Gold purchase updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Delete a gold purchase and REVERSE its balance effects.
     * Cash is refunded to the branch; gold is removed from stock.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        // Only branch_manager and director may delete
        if (!in_array($user->role, ['branch_manager', 'director'], true)) {
            abort(403, 'You are not allowed to delete gold purchases.');
        }

        $purchase = Transaction::with(['items'])
            ->where('transaction_type', 'gold_purchase')
            ->findOrFail($id);

        // Branch managers can only delete their own branch's records
        if ($user->role === 'branch_manager' && $purchase->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this purchase.');
        }

        try {
            DB::beginTransaction();

            $branchId    = $purchase->branch_id;
            $amount      = (float) $purchase->amount;
            $totalWeight = (float) $purchase->items->sum('weight');
            $code        = $purchase->transaction_code;

            // ---- 1. Refund cash to the branch ----
            $cash = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->lockForUpdate()
                ->first();

            if ($cash) {
                $cash->amount = $cash->amount + $amount;
                $cash->save();
            } else {
                BranchBalance::create([
                    'branch_id'    => $branchId,
                    'balance_type' => 'cash',
                    'amount'       => $amount,
                ]);
            }

            // ---- 2. Remove gold from the branch ----
            $branch = Branch::lockForUpdate()->find($branchId);
            if (!$branch) {
                throw new \RuntimeException('Branch not found for this purchase.');
            }

            $currentGold = (float) ($branch->gold_available ?? 0);
            if ($currentGold < $totalWeight) {
                throw new \RuntimeException(sprintf(
                    'Cannot delete: branch gold (%sg) is less than purchase weight (%sg).',
                    number_format($currentGold, 2),
                    number_format($totalWeight, 2)
                ));
            }
            $branch->gold_available = $currentGold - $totalWeight;
            $branch->save();

            // ---- 3. Mirror the gold change into branch_balances (gold) ----
            $goldBal = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')
                ->lockForUpdate()
                ->first();

            if ($goldBal) {
                $goldBal->amount = max(0, $goldBal->amount - $totalWeight);
                $goldBal->save();
            }

            // ---- 4. Delete items then the transaction ----
            $purchase->items()->delete();
            $purchase->delete();

            \Log::info('Gold purchase deleted', [
                'transaction_id'   => $id,
                'transaction_code' => $code,
                'branch_id'        => $branchId,
                'cash_refunded'    => $amount,
                'gold_removed_g'   => $totalWeight,
                'deleted_by'       => $user->id,
                'deleted_at'       => now(),
            ]);

            DB::commit();

            return redirect()->route('transactions.gold.purchases.index')
                ->with('success', sprintf(
                    'Gold purchase %s deleted. TZS %s refunded to branch cash, %sg removed from gold stock.',
                    $code,
                    number_format($amount, 0),
                    number_format($totalWeight, 2)
                ));

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gold purchase delete failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete gold purchase: ' . $e->getMessage());
        }
    }

    public function receive($id)
    {
        $purchase = Transaction::with('items')->where('transaction_type', 'gold_purchase')->findOrFail($id);
        if ($purchase->status !== 'pending') {
            return redirect()->route('transactions.gold.purchases.index')
                ->with('error', 'Already processed.');
        }
        try {
            DB::beginTransaction();
            $purchase->status = 'completed';
            $purchase->verified_by = Auth::id();
            $purchase->save();
            DB::commit();
            return redirect()->route('transactions.gold.purchases.index')
                ->with('success', 'Gold purchase marked as received!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed: '.$e->getMessage());
        }
    }

    // ============ SMS — matches PHP page behaviour ============
    private function sendPurchaseNotifications($transaction, $supplier, $branch, $totalWeight, $itemDetails)
    {
        try {
            $sms = app(SMSController::class);
            $avgPurity = count($itemDetails)
                ? collect($itemDetails)->avg('purity')
                : 0;

            // 1) Supplier — thank-you (exact PHP text)
            if ($supplier && $supplier->contact) {
                $phone = $sms->formatPhoneNumber($supplier->contact);
                if ($phone) {
                    $msg = "Hongera! Asante kwa kuchagua NGEMBA MINING. Tunathamini biashara yako , kwetu KILA GRAM INATHAMANI.";
                    $sms->sendSms($phone, $msg, 'NGEMBA MINE');
                }
            }

            // 2) Branch manager
            $manager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $transaction->branch_id)
                ->whereNotNull('phone_number')->first();

            if ($manager) {
                $phone = $sms->formatPhoneNumber($manager->phone_number);
                if ($phone) {
                    $msg = "GOLD PURCHASE - YOUR BRANCH\n"
                         . "Branch: ".($branch->name ?? 'N/A')."\n"
                         . "Date: ".$transaction->created_at->format('d/m/Y H:i')."\n"
                         . "Supplier: ".($supplier->name ?? 'Walk-in Supplier')."\n"
                         . "Amount: TZS ".number_format($transaction->amount)."\n"
                         . "Weight: ".number_format($totalWeight, 2)."g\n"
                         . "Purity: ".number_format($avgPurity, 1)."%\n"
                         . "Txn ID: ".$transaction->transaction_code;
                    $sms->sendSms($phone, $msg, 'NGEMBA MINE');
                }
            }

            // 3) Directors
            foreach (User::where('role', 'director')->whereNotNull('phone_number')->get() as $d) {
                $phone = $sms->formatPhoneNumber($d->phone_number);
                if (!$phone) continue;
                $msg = "GOLD PURCHASE NOTIFICATION - ".($branch->name ?? 'N/A')."\n"
                     . "Date: ".$transaction->created_at->format('d/m/Y H:i')."\n"
                     . "Supplier: ".($supplier->name ?? 'Walk-in Supplier')."\n"
                     . "Amount: TZS ".number_format($transaction->amount)."\n"
                     . "Weight: ".number_format($totalWeight, 2)."g\n"
                     . "Average Purity: ".number_format($avgPurity, 1)."%\n"
                     . "Txn: ".$transaction->transaction_code;
                $sms->sendSms($phone, $msg, 'NGEMBA MINE');
            }
        } catch (\Exception $e) {
            \Log::error('SMS failed: '.$e->getMessage());
        }
    }

    private function generateTransactionCode()
    {
        $prefix = 'GP'.date('Ymd');
        $last = Transaction::where('transaction_code', 'like', $prefix.'%')
            ->orderByDesc('id')->first();
        $n = $last ? intval(substr($last->transaction_code, -4)) + 1 : 1;
        return $prefix.'-'.str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}