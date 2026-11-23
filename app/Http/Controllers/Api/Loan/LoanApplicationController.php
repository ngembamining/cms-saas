<?php

namespace App\Http\Controllers\Api\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;

class LoanApplicationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1000',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string|max:500',
            'application_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();
            $user = $request->user();

            $loan = Loan::create([
                'branch_id' => $request->branch_id,
                'suppliers_id' => $request->supplier_id,
                'amount' => $request->amount,
                'interest_rate' => $request->interest_rate,
                'duration_months' => $request->duration_months,
                'description' => $request->description,
                'created_by' => $user->id,
                'status' => 'pending',
                'application_date' => $request->application_date,
            ]);

            DB::commit();

            // NOTE: To trigger SMS, you can call the web controller here, but it's optional.
            // $webController = app(\App\Http\Controllers\Loan\LoanApplicationController::class);
            // $webController->... (skip for now to keep API fast)

            return response()->json([
                'status' => true,
                'message' => "Loan application #{$loan->id} submitted successfully.",
                'data' => ['loan_id' => $loan->id]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}