<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\BranchBalance;
use App\Models\ExchangeRate;
use App\Models\User;

class GoldPurchaseController extends Controller
{
    protected $dividerConstant = 12;
    protected $defaultMarkupRate = 10;
    protected $allowedPaymentMethods = ['cash', 'bank_transfer', 'mobile_money'];

    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Transaction::with(['branch', 'supplier', 'items'])
            ->where('transaction_type', 'gold_purchase');

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        $purchases = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $purchases->map(function ($tx) {
                return [
                    'id' => (string) $tx->id,
                    'code' => $tx->transaction_code,
                    'branch_name' => $tx->branch->name ?? 'Unknown',
                    'supplier_name' => $tx->supplier->name ?? 'Walk-in',
                    'amount_tzs' => (float) $tx->amount,
                    'total_weight' => (float) $tx->items->sum('weight'),
                    'status' => strtoupper($tx->status),
                    'date' => $tx->created_at->format('d M Y, h:i A'),
                ];
            }),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'total' => $purchases->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'         => 'required|exists:branches,id',
            'supplier_id'       => 'nullable|integer|min:0',
            'weights'           => 'required|array|min:1',
            'weights.*'         => 'required|numeric|min:0.01',
            'purities'          => 'required|array',
            'purities.*'        => 'required|numeric|min:0.01|max:100',
            'prices'            => 'required|array',
            'prices.*'          => 'required|numeric|min:0.01',
            'markup_rate'       => 'required|numeric|min:0|max:100',
            'last_digits'       => 'nullable|string|max:4',
            'payment_method'    => 'required|in:cash,bank_transfer,mobile_money',
            'description'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = (int) $request->branch_id;
            $supplierId = (int) ($request->supplier_id ?? 0);
            $markupRate = (float) $request->markup_rate;

            $totalWeight = 0;
            $totalAmount = 0;
            $itemsData = [];

            foreach ($request->weights as $i => $w) {
                $weight = (float) $w;
                $purity = (float) ($request->purities[$i] ?? 0);
                $price  = (float) ($request->prices[$i] ?? 0);

                $itemAmount = ($weight * ($purity / 100) * $price) / $this->dividerConstant * (1 - ($markupRate / 100));

                $totalWeight += $weight;
                $totalAmount += $itemAmount;

                $itemsData[] = ['weight' => $weight, 'purity' => $purity, 'price' => $price];
            }

            $cashBalance = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'cash')->first();
            if (!$cashBalance || $cashBalance->amount < $totalAmount) {
                throw new \RuntimeException("Insufficient cash balance.");
            }

            $totalAmount = round($totalAmount, 2);
            $transactionCode = 'GP' . date('Ymd') . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'transaction_code'  => $transactionCode,
                'branch_id'         => $branchId,
                'transaction_type'  => 'gold_purchase',
                'amount'            => $totalAmount,
                'supplier_id'       => $supplierId ?: null,
                'description'       => $request->description ?? 'Gold purchase via mobile app',
                'payment_method'    => $request->payment_method,
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

            $cashBalance->amount -= $totalAmount;
            $cashBalance->save();

            $branch = Branch::find($branchId);
            $branch->gold_available = ($branch->gold_available ?? 0) + $totalWeight;
            $branch->save();

            $goldBal = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'gold')->first();
            if ($goldBal) {
                $goldBal->amount += $totalWeight;
                $goldBal->save();
            } else {
                BranchBalance::create(['branch_id' => $branchId, 'balance_type' => 'gold', 'amount' => $totalWeight]);
            }

            DB::commit();

            // ✅ FIX: Fully-qualified namespace for the Web SMS Controller
            try {
                $sms = app(\App\Http\Controllers\Communication\SMSController::class);
                $manager = User::where('role', 'branch_manager')
                    ->where('assigned_branch', $branchId)
                    ->whereNotNull('phone_number')->first();

                if ($manager) {
                    $phone = $sms->formatPhoneNumber($manager->phone_number);
                    if ($phone) {
                        $sms->sendSms($phone, "Gold Purchase: {$totalWeight}g bought for TZS " . number_format($totalAmount) . ". Ref: {$transactionCode}", 'NGEMBA MINE');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SMS Notification Failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => "Gold purchase recorded successfully! {$totalWeight}g purchased for TZS " . number_format($totalAmount),
                'data' => ['transaction_code' => $transactionCode]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, $id)
    {
        $tx = Transaction::with(['branch', 'supplier', 'items'])
            ->where('transaction_type', 'gold_purchase')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => (string) $tx->id,
                'code' => $tx->transaction_code,
                'branch_name' => $tx->branch->name,
                'supplier_name' => $tx->supplier->name ?? 'Walk-in',
                'amount_tzs' => (float) $tx->amount,
                'total_weight' => (float) $tx->items->sum('weight'),
                'items' => $tx->items->map(fn($i) => [
                    'weight_grams' => (float) $i->weight,
                    'purity_percentage' => (float) $i->purity,
                    'price_per_gram' => (float) $i->agreed_price,
                ]),
                'status' => strtoupper($tx->status),
                'date' => $tx->created_at->format('d M Y, h:i A'),
            ]
        ]);
    }
}