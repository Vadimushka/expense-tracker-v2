<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthYearRequest;
use App\Http\Requests\TogglePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(MonthYearRequest $request): Response
    {
        $month = (int) $request->month;
        $year = (int) $request->year;

        return Inertia::render('payments', [
            'paymentSummary' => $this->paymentService->getSummary($month, $year),
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function toggle(TogglePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $isPaid = $this->paymentService->toggle(
            $validated['expense_id'],
            $validated['month'],
            $validated['year'],
        );

        return response()->json(['is_paid' => $isPaid]);
    }
}
