<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreReceiptRequest;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    /**
     * Record a receipt.
     */
    public function storeReceipt(StoreReceiptRequest $request): JsonResponse
    {
        $receipt = Receipt::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'receipt' => [
                ...$receipt->load('user')->toArray(),
                'date' => $receipt->date?->format('Y-m-d'),
            ],
        ], 201);
    }

    /**
     * Record a payment.
     */
    public function storePayment(StorePaymentRequest $request): JsonResponse
    {
        $payment = Payment::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'payment' => [
                ...$payment->load('user')->toArray(),
                'date' => $payment->date?->format('Y-m-d'),
            ],
        ], 201);
    }

    /**
     * Record an expense.
     */
    public function storeExpense(StoreExpenseRequest $request): JsonResponse
    {
        $expense = Expense::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'expense' => [
                ...$expense->load('user')->toArray(),
                'date' => $expense->date?->format('Y-m-d'),
            ],
        ], 201);
    }

    /**
     * Return the current cash position from all finance entries.
     */
    public function cashSummary(): JsonResponse
    {
        $receipts = (float) Receipt::query()->sum('amount');
        $payments = (float) Payment::query()->sum('amount');
        $expenses = (float) Expense::query()->sum('amount');

        return response()->json([
            'cash_summary' => [
                'receipts' => number_format($receipts, 2, '.', ''),
                'payments' => number_format($payments, 2, '.', ''),
                'expenses' => number_format($expenses, 2, '.', ''),
                'current_balance' => number_format($receipts - $payments - $expenses, 2, '.', ''),
            ],
        ]);
    }
}
