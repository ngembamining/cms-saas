<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\BranchBalance;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Http\Controllers\Communication\SMSController;

class GoldSaleController extends Controller
{
    protected $dividerConstant = 12;
    protected $defaultMarkupRate = 10;

    // DB enum: cash, bank_transfer, mobile_money, credit
    protected $allowedPaymentMethods = ['cash', 'bank_transfer', 'mobile_money'];

    /* =========================================================
     |  INDEX
     ========================================================= */
    public function index()
    {
        $user = Auth::user();

        $sales = Transaction::with(['branch', 'user', 'customer', 'items'])
            ->where('transaction_type', 'gold_sale')
            ->when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalSales = Transaction::where('transaction_type', 'gold_sale')
            ->when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
            ->sum('amount');

        $totalWeight = TransactionItem::whereHas('transaction', function ($q) use ($user) {
            $q->where('transaction_type', 'gold_sale')
              ->when($user->role !== 'director', fn($qq) => $qq->where('branch_id', $user->assigned_branch));
        })->sum('weight');

        $pendingCount = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'pending')
            ->when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
            ->count();

        return view('transactions.gold-sales.index',
            compact('sales', 'totalSales', 'totalWeight', 'pendingCount'));
    }

    /* =========================================================
     |  CREATE
     ========================================================= */
    public function create()
    {
        $user = Auth::user();

        $branches = $user->role === 'director'
            ? Branch::all()
            : Branch::where('id', $user->assigned_branch)->get();

        $customers = Customer::when($user->role !== 'director', fn($q) => $q->where('branch_id', $user->assigned_branch))
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
                // Source of truth = MIN of both tables
                $cashRow  = BranchBalance::where('branch_id', $user->assigned_branch)
                    ->where('balance_type', 'cash')->first();
                $goldRow  = BranchBalance::where('branch_id', $user->assigned_branch)
                    ->where('balance_type', 'gold')->first();

                $branchCash = (float) ($cashRow->amount ?? 0);
                $branchGold = min(
                    (float) ($branch->gold_available ?? 0),
                    (float) ($goldRow->amount ?? 0)
                );
            }
        }

        $requestToken = (string) Str::uuid();

        $recentPrices = TransactionItem::whereHas('transaction', function ($q) use ($user) {
            $q->where('transaction_type', 'gold_sale')
              ->when($user->role !== 'director', fn($qq) => $qq->where('branch_id', $user->assigned_branch))
              ->where('created_at', '>=', now()->subDays(7));
        })
        ->select('purity', 'agreed_price', 'weight')
        ->orderByDesc('id')->limit(5)->get();

        $defaultMarkupRate = $this->defaultMarkupRate;
        $dividerConstant   = $this->dividerConstant;
        $paymentMethods    = $this->allowedPaymentMethods;

        return view('transactions.gold-sales.create', compact(
            'branches', 'customers', 'currentGoldRate', 'branch',
            'branchCash', 'branchGold', 'user', 'requestToken',
            'recentPrices', 'defaultMarkupRate', 'dividerConstant', 'paymentMethods'
        ));
    }

    /* =========================================================
     |  STORE
     ========================================================= */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id'         => 'required|exists:branches,id',
            'customer_id'       => 'required|exists:customers,id',
            'weights'           => 'required|array|min:1',
            'weights.*'         => 'required|numeric|min:0.01',
            'purities'          => 'required|array',
            'purities.*'        => 'required|numeric|min:0.01|max:100',
            'prices'            => 'required|array',
            'prices.*'          => 'required|numeric|min:0.01',
            'markup_rate'       => 'nullable|numeric|min:0|max:100',
            'last_digits'       => 'nullable|string|max:4',
            'payment_method'    => 'required|in:' . implode(',', $this->allowedPaymentMethods),
            'payment_reference' => 'nullable|string|max:100',
            'description'       => 'nullable|string|max:500',
            'request_token'     => 'nullable|string|max:64',
        ]);

        // --- Session duplicate guard ---
        $requestToken = $request->input('request_token');
        if ($requestToken) {
            $tokens = session('processed_gold_sale_tokens', []);
            if (in_array($requestToken, $tokens, true)) {
                return redirect()->route('transactions.gold.sales.index')
                    ->with('success', 'This sale was already recorded — duplicate submission ignored.');
            }
        }

        try {
            DB::beginTransaction();

            $user       = Auth::user();
            $branchId   = (int) $request->branch_id;
            $customerId = (int) $request->customer_id;
            $markupRate = (float) ($request->markup_rate ?? $this->defaultMarkupRate);

            // ---- Compute totals SERVER-SIDE only ----
            $totalWeight = 0;
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($request->weights as $i => $w) {
                $weight = (float) $w;
                $purity = (float) ($request->purities[$i] ?? 0);
                $price  = (float) ($request->prices[$i] ?? 0);

                if ($weight <= 0) throw new \RuntimeException("Item ".($i+1).": Invalid weight");
                if ($purity <= 0 || $purity > 100) throw new \RuntimeException("Item ".($i+1).": Invalid purity");
                if ($price  <= 0) throw new \RuntimeException("Item ".($i+1).": Invalid price");

                // Same formula as purchase: /12 × (1 - markup/100)
                $itemAmount = ($weight * ($purity / 100) * $price) / $this->dividerConstant
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

            // ---- Last-4-digit adjustment (server-side) ----
            $lastDigits = preg_replace('/[^0-9]/', '', (string) $request->input('last_digits', ''));
            if (strlen($lastDigits) === 4) {
                $base = floor($totalAmount / 10000) * 10000;
                $adj  = $base + (int) $lastDigits;
                if (abs($adj - $totalAmount) < 10000) {
                    $totalAmount = $adj;
                }
            }
            $totalAmount = round($totalAmount, 2);

            // ---- Gold inventory check (uses MIN of both sources) ----
            $goldRow = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')
                ->lockForUpdate()
                ->first();
            $balAmt = (float) ($goldRow->amount ?? 0);

            $branchRow = Branch::lockForUpdate()->find($branchId);
            if (!$branchRow) throw new \RuntimeException('Branch not found.');
            $branchAmt = (float) ($branchRow->gold_available ?? 0);

            $availableGold = min($balAmt, $branchAmt);

            if ($availableGold < $totalWeight) {
                throw new \RuntimeException(sprintf(
                    'Insufficient gold inventory. Available: %sg, Required: %sg',
                    number_format($availableGold, 2),
                    number_format($totalWeight, 2)
                ));
            }

            // ---- DB duplicate guard (30s window) ----
            $dup = Transaction::where('transaction_type', 'gold_sale')
                ->where('branch_id', $branchId)
                ->where('customer_id', $customerId)
                ->where('amount', $totalAmount)
                ->where('recorded_by', $user->id)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->exists();
            if ($dup) {
                DB::rollBack();
                return redirect()->route('transactions.gold.sales.index')
                    ->with('success', 'This sale was already recorded — duplicate submission ignored.');
            }

            $transactionCode = $this->generateTransactionCode();

            // ---- Create Transaction ----
            $transaction = Transaction::create([
                'transaction_code'  => $transactionCode,
                'branch_id'         => $branchId,
                'transaction_type'  => 'gold_sale',
                'amount'            => $totalAmount,
                'customer_id'       => $customerId,
                'description'       => $request->description ?? 'Gold sale to customer',
                'payment_method'    => $request->payment_method,
                'payment_reference' => $request->payment_reference,
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

            // =====================================================
            //  BALANCE UPDATES — mirror purchase module pattern
            // =====================================================

            // 1) GOLD DOWN in branch_balances
            if ($goldRow) {
                $goldRow->amount = $goldRow->amount - $totalWeight;
                $goldRow->save();
            } else {
                BranchBalance::create([
                    'branch_id'    => $branchId,
                    'balance_type' => 'gold',
                    'amount'       => 0,
                ]);
            }

            // 2) GOLD DOWN in branches.gold_available  <-- BUG #1 FIX
            $branchRow->gold_available = ($branchRow->gold_available ?? 0) - $totalWeight;
            $branchRow->save();

            // 3) CASH UP in branch_balances
            $cashRow = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->lockForUpdate()
                ->first();

            if ($cashRow) {
                $cashRow->amount = $cashRow->amount + $totalAmount;
                $cashRow->save();
            } else {
                BranchBalance::create([
                    'branch_id'    => $branchId,
                    'balance_type' => 'cash',
                    'amount'       => $totalAmount,
                ]);
            }

            DB::commit();

            // ---- Token cleanup ----
            if ($requestToken) {
                $tokens[] = $requestToken;
                if (count($tokens) > 50) $tokens = array_slice($tokens, -50);
                session(['processed_gold_sale_tokens' => $tokens]);
            }

            // ---- SMS Notifications ----
            $customer = Customer::find($customerId);
            $this->sendSaleNotifications($transaction, $customer, $branchRow, $totalWeight, $itemsData);

            return redirect()->route('transactions.gold.sales.index')
                ->with('success', sprintf(
                    'Gold sale recorded successfully! %sg sold for TZS %s. Transaction ID: %s.',
                    number_format($totalWeight, 2),
                    number_format($totalAmount),
                    $transactionCode
                ));

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gold sale failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to record gold sale: ' . $e->getMessage())
                ->withInput();
        }
    }

    /* =========================================================
     |  SHOW
     ========================================================= */
    public function show($id)
    {
        $sale = Transaction::with(['branch', 'user', 'customer', 'items'])
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        $user = Auth::user();
        if ($user->role !== 'director' && $sale->branch_id != $user->assigned_branch) {
            abort(403);
        }
        return view('transactions.gold-sales.show', compact('sale'));
    }

    /* =========================================================
     |  EDIT
     ========================================================= */
    public function edit($id)
    {
        $sale = Transaction::with('items')
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        if ($sale->status !== 'pending') {
            return redirect()->route('transactions.gold.sales.index')
                ->with('error', 'This sale cannot be edited (status: ' . $sale->status . ').');
        }

        $user = Auth::user();
        if ($user->role !== 'director' && $sale->branch_id != $user->assigned_branch) {
            abort(403);
        }

        $branches = Branch::all();
        $customers = Customer::all();
        $defaultMarkupRate = $this->defaultMarkupRate;
        $dividerConstant   = $this->dividerConstant;
        $paymentMethods    = $this->allowedPaymentMethods;

        return view('transactions.gold-sales.edit',
            compact('sale', 'branches', 'customers', 'defaultMarkupRate', 'dividerConstant', 'paymentMethods'));
    }

    /* =========================================================
     |  UPDATE (only pending sales)
     ========================================================= */
    public function update(Request $request, $id)
    {
        $sale = Transaction::with('items')
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        if ($sale->status !== 'pending') {
            return redirect()->route('transactions.gold.sales.index')
                ->with('error', 'Cannot edit — this sale is already ' . $sale->status . '.');
        }

        $request->validate([
            'branch_id'         => 'required|exists:branches,id',
            'customer_id'       => 'required|exists:customers,id',
            'weights'           => 'required|array|min:1',
            'weights.*'         => 'required|numeric|min:0.01',
            'purities'          => 'required|array',
            'purities.*'        => 'required|numeric|min:0.01|max:100',
            'prices'            => 'required|array',
            'prices.*'          => 'required|numeric|min:0.01',
            'markup_rate'       => 'nullable|numeric|min:0|max:100',
            'payment_method'    => 'nullable|in:' . implode(',', $this->allowedPaymentMethods),
            'payment_reference' => 'nullable|string|max:100',
            'description'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $markupRate = (float) ($request->markup_rate ?? $this->defaultMarkupRate);
            $totalWeight = 0;
            $totalAmount = 0;

            foreach ($request->weights as $i => $w) {
                $weight = (float) $w;
                $purity = (float) ($request->purities[$i] ?? 0);
                $price  = (float) ($request->prices[$i] ?? 0);

                $totalWeight += $weight;
                $totalAmount += ($weight * ($purity / 100) * $price) / $this->dividerConstant
                              * (1 - ($markupRate / 100));
            }
            $totalAmount = round($totalAmount, 2);

            $sale->update([
                'branch_id'         => $request->branch_id,
                'customer_id'       => $request->customer_id,
                'amount'            => $totalAmount,
                'description'       => $request->description ?? 'Gold sale',
                'payment_method'    => $request->payment_method ?? $sale->payment_method,
                'payment_reference' => $request->payment_reference ?? $sale->payment_reference,
            ]);

            $sale->items()->delete();
            foreach ($request->weights as $i => $w) {
                TransactionItem::create([
                    'transaction_id' => $sale->id,
                    'weight'         => $w,
                    'purity'         => $request->purities[$i] ?? 0,
                    'agreed_price'   => $request->prices[$i] ?? 0,
                ]);
            }

            DB::commit();
            return redirect()->route('transactions.gold.sales.index')
                ->with('success', 'Gold sale updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update: ' . $e->getMessage())
                ->withInput();
        }
    }

    /* =========================================================
     |  DESTROY — with full balance reversal  (BUG #3 FIX)
     ========================================================= */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['branch_manager', 'director'], true)) {
            abort(403, 'You are not allowed to delete gold sales.');
        }

        $sale = Transaction::with('items')
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        if ($user->role === 'branch_manager' && $sale->branch_id != $user->assigned_branch) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $branchId    = $sale->branch_id;
            $amount      = (float) $sale->amount;
            $totalWeight = (float) $sale->items->sum('weight');
            $code        = $sale->transaction_code;

            // ---- 1. Take cash back (sale added it) ----
            $cash = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->lockForUpdate()->first();

            if ($cash) {
                if ($cash->amount < $amount) {
                    throw new \RuntimeException(sprintf(
                        'Cannot delete: branch cash (%s TZS) is less than sale amount (%s TZS).',
                        number_format($cash->amount, 0),
                        number_format($amount, 0)
                    ));
                }
                $cash->amount -= $amount;
                $cash->save();
            }

            // ---- 2. Return gold to branch_balances ----
            $goldBal = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')
                ->lockForUpdate()->first();

            if ($goldBal) {
                $goldBal->amount += $totalWeight;
                $goldBal->save();
            } else {
                BranchBalance::create([
                    'branch_id'    => $branchId,
                    'balance_type' => 'gold',
                    'amount'       => $totalWeight,
                ]);
            }

            // ---- 3. Return gold to branches.gold_available ----
            $branch = Branch::lockForUpdate()->find($branchId);
            if ($branch) {
                $branch->gold_available = ($branch->gold_available ?? 0) + $totalWeight;
                $branch->save();
            }

            // ---- 4. Delete items then transaction ----
            $sale->items()->delete();
            $sale->delete();

            Log::info('Gold sale deleted', [
                'transaction_id'   => $id,
                'transaction_code' => $code,
                'branch_id'        => $branchId,
                'cash_removed'     => $amount,
                'gold_returned_g'  => $totalWeight,
                'deleted_by'       => $user->id,
                'deleted_at'       => now(),
            ]);

            DB::commit();

            return redirect()->route('transactions.gold.sales.index')
                ->with('success', sprintf(
                    'Gold sale %s reversed. TZS %s removed from cash, %sg returned to gold stock.',
                    $code,
                    number_format($amount, 0),
                    number_format($totalWeight, 2)
                ));

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gold sale delete failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /* =========================================================
     |  COMPLETE (pending → completed) — no balance change
     |  because balances already updated at creation time.
     ========================================================= */
    public function complete($id)
    {
        $sale = Transaction::with('items')
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        if ($sale->status !== 'pending') {
            return redirect()->route('transactions.gold.sales.index')
                ->with('error', 'This sale has already been processed.');
        }

        try {
            DB::beginTransaction();
            $sale->status = 'completed';
            $sale->verified_by = Auth::id();
            $sale->save();
            DB::commit();

            return redirect()->route('transactions.gold.sales.index')
                ->with('success', 'Gold sale completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /* =========================================================
     |  SMS NOTIFICATIONS
     ========================================================= */
    private function sendSaleNotifications($transaction, $customer, $branch, $totalWeight, $itemDetails)
    {
        try {
            $sms = app(SMSController::class);
            $avgPrice = collect($itemDetails)->avg('price') ?? 0;

            // 1) Customer
            if ($customer && $customer->contact) {
                $phone = $sms->formatPhoneNumber($customer->contact);
                if ($phone) {
                    $msg = $this->getCustomerSaleMessage($customer, $transaction, $totalWeight, $avgPrice);
                    $sms->sendSms($phone, $msg, 'NGEMBA MINE');
                }
            }

            // 2) Branch Manager
            $manager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $transaction->branch_id)
                ->whereNotNull('phone_number')->first();

            if ($manager) {
                $phone = $sms->formatPhoneNumber($manager->phone_number);
                if ($phone) {
                    $msg = $this->getManagerSaleMessage($manager, $customer, $transaction, $totalWeight, $avgPrice, $branch);
                    $sms->sendSms($phone, $msg, 'NGEMBA MINE');
                }
            }

            // 3) Directors
            foreach (User::where('role', 'director')->whereNotNull('phone_number')->get() as $d) {
                $phone = $sms->formatPhoneNumber($d->phone_number);
                if (!$phone) continue;
                $msg = $this->getDirectorSaleMessage($d, $customer, $transaction, $totalWeight, $avgPrice, $branch);
                $sms->sendSms($phone, $msg, 'NGEMBA MINE');
            }
        } catch (\Exception $e) {
            Log::error('Sale SMS failed: ' . $e->getMessage());
        }
    }

    private function getCustomerSaleMessage($customer, $transaction, $totalWeight, $avgPrice)
    {
        $tpl = DB::table('sms_templates')->where('name', 'gold_sale_customer')->first();
        if ($tpl) {
            return str_replace(
                ['{customer_name}', '{gold_weight}', '{price}', '{total_amount}', '{transaction_code}'],
                [
                    $customer->name,
                    number_format($totalWeight, 2),
                    number_format($avgPrice, 0),
                    number_format($transaction->amount, 0),
                    $transaction->transaction_code,
                ],
                $tpl->message
            );
        }
        return "Dear {$customer->name}, Thank you for your gold sale of "
             . number_format($totalWeight, 2) . "g. Total: TZS "
             . number_format($transaction->amount, 0)
             . ". Transaction: {$transaction->transaction_code}. - Ngemba Mining";
    }

    private function getManagerSaleMessage($manager, $customer, $transaction, $totalWeight, $avgPrice, $branch)
    {
        $tpl = DB::table('sms_templates')->where('name', 'gold_sale_manager')->first();
        if ($tpl) {
            return str_replace(
                ['{gold_weight}', '{customer_name}', '{price}', '{total_amount}', '{branch_name}', '{transaction_code}'],
                [
                    number_format($totalWeight, 2),
                    $customer->name ?? 'Unknown',
                    number_format($avgPrice, 0),
                    number_format($transaction->amount, 0),
                    $branch->name ?? 'N/A',
                    $transaction->transaction_code,
                ],
                $tpl->message
            );
        }
        return "Gold Sale Alert: " . number_format($totalWeight, 2) . "g sold to "
             . ($customer->name ?? 'Unknown') . ". Amount: TZS "
             . number_format($transaction->amount, 0) . ". Branch: "
             . ($branch->name ?? 'N/A') . ". Txn: {$transaction->transaction_code}.";
    }

    private function getDirectorSaleMessage($director, $customer, $transaction, $totalWeight, $avgPrice, $branch)
    {
        $tpl = DB::table('sms_templates')->where('name', 'gold_sale_director')->first();
        if ($tpl) {
            return str_replace(
                ['{branch_name}', '{gold_weight}', '{customer_name}', '{total_amount}', '{transaction_code}', '{date}'],
                [
                    $branch->name ?? 'N/A',
                    number_format($totalWeight, 2),
                    $customer->name ?? 'Unknown',
                    number_format($transaction->amount, 0),
                    $transaction->transaction_code,
                    $transaction->created_at->format('d/m/Y H:i'),
                ],
                $tpl->message
            );
        }
        return "Gold Sale Report: " . ($branch->name ?? 'N/A') . " sold "
             . number_format($totalWeight, 2) . "g to " . ($customer->name ?? 'Unknown')
             . ". Amount: TZS " . number_format($transaction->amount, 0)
             . ". Txn: {$transaction->transaction_code}. Date: "
             . $transaction->created_at->format('d/m/Y H:i');
    }

    /* =========================================================
     |  TRANSACTION CODE
     ========================================================= */
    private function generateTransactionCode()
    {
        $prefix = 'GS' . date('Ymd');
        $last = Transaction::where('transaction_code', 'like', $prefix . '%')
            ->orderByDesc('id')->first();
        $n = $last ? intval(substr($last->transaction_code, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}