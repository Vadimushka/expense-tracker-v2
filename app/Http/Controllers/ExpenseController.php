<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthYearRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService) {}

    public function index(MonthYearRequest $request): Response
    {
        $month = (int) $request->month;
        $year = (int) $request->year;

        $expenses = Expense::with('category')
            ->forMonth($month, $year)
            ->orderByRaw('COALESCE(day_of_month, EXTRACT(DAY FROM COALESCE(date, due_date))::integer)')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('expenses', [
            'expenses' => ExpenseResource::collection($expenses),
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function grouped(): JsonResponse
    {
        return response()->json($this->expenseService->getGrouped());
    }

    public function byIds(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|string']);

        $ids = array_map('intval', explode(',', $request->ids));

        return response()->json(ExpenseResource::collection($this->expenseService->getByIds($ids)));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! ($validated['is_periodic'] ?? false)) {
            if (empty($validated['date']) && empty($validated['due_date'])) {
                return back()->withErrors(['date' => 'Укажите хотя бы одну дату.']);
            }
        }

        $this->expenseService->create($validated);

        return back();
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back();
    }
}
