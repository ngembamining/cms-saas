<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\BranchBalance;
use App\Models\User;

class GoldSaleController extends Controller
{
    protected $dividerConstant = 12;
    protected $defaultMarkupRate = 10;
    protected $allowedPaymentMethods = ['cash', 'bank_transfer', 'mobile_money'];

    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Transaction::with(['branch', 'customer', 'items'])
            ->where('transaction_type', 'gold_sale');

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        $sales = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $sales->map(function ($tx) {
                return [
                    'id' => (string) $tx->id,
                    'code' => $tx->transaction_code,
                    'branch_name' => $tx->branch->name ?? 'Unknown',
                    'customer_name' => $tx->customer->name ?? 'Unknown',
                    'amount_tzs' => (float) $tx->amount,
                    'total_weight' => (float) $tx->items->sum('weight'),
                    'status' => strtoupper($tx->status),
                    'date' => $tx->created_at->format('d M Y, h:i A'),
                ];
            }),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'total' => $sales->total(),
            ]
        ]);
    }

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
            'payment_method'    => 'required|in:cash,bank_transfer,mobile_money',
            'payment_reference' => 'nullable|string|max:100',
            'description'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = (int) $request->branch_id;
            $customerId = (int) $request->customer_id;
            $markupRate = (float) ($request->markup_rate ?? $this->defaultMarkupRate);

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

            $goldRow = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'gold')->lockForUpdate()->first();
            $branchRow = Branch::lockForUpdate()->find($branchId);

            $availableGold = min((float)($goldRow->amount ?? 0), (float)($branchRow->gold_available ?? 0));

            if ($availableGold < $totalWeight) {
                throw new \RuntimeException("Insufficient gold inventory. Available: {$availableGold}g, Required: {$totalWeight}g");
            }

            $totalAmount = round($totalAmount, 2);
            $transactionCode = 'GS' . date('Ymd') . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'transaction_code'  => $transactionCode,
                'branch_id'         => $branchId,
                'transaction_type'  => 'gold_sale',
                'amount'            => $totalAmount,
                'customer_id'       => $customerId,
                'description'       => $request->description ?? 'Gold sale via mobile app',
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

            if ($goldRow) {
                $goldRow->amount -= $totalWeight;
                $goldRow->save();
            }

            $branchRow->gold_available = ($branchRow->gold_available ?? 0) - $totalWeight;
            $branchRow->save();

            $cashRow = BranchBalance::where('branch_id', $branchId)->where('balance_type', 'cash')->lockForUpdate()->first();
            if ($cashRow) {
                $cashRow->amount += $totalAmount;
                $cashRow->save();
            } else {
                BranchBalance::create(['branch_id' => $branchId, 'balance_type' => 'cash', 'amount' => $totalAmount]);
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
                        $sms->sendSms($phone, "Gold Sale: {$totalWeight}g sold for TZS " . number_format($totalAmount) . ". Ref: {$transactionCode}", 'NGEMBA MINE');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SMS Notification Failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => "Gold sale recorded successfully! {$totalWeight}g sold for TZS " . number_format($totalAmount),
                'data' => ['transaction_code' => $transactionCode]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, $id)
    {
        $tx = Transaction::with(['branch', 'customer', 'items'])
            ->where('transaction_type', 'gold_sale')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => (string) $tx->id,
                'code' => $tx->transaction_code,
                'branch_name' => $tx->branch->name,
                'customer_name' => $tx->customer->name ?? 'Unknown',
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