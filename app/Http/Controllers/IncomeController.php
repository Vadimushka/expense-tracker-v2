<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthYearRequest;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller
{
    public function __construct(private IncomeService $incomeService) {}

    public function index(MonthYearRequest $request): Response
    {
        $month = (int) $request->month;
        $year = (int) $request->year;

        $incomes = Income::forMonth($month, $year)
            ->orderByRaw('COALESCE(day_of_month, EXTRACT(DAY FROM date)::integer)')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('incomes', [
            'incomes' => IncomeResource::collection($incomes),
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function grouped(): JsonResponse
    {
        return response()->json($this->incomeService->getGrouped());
    }

    public function byKey(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'is_periodic' => 'required',
        ]);

        $incomes = $this->incomeService->getByKey(
            $request->type,
            filter_var($request->is_periodic, FILTER_VALIDATE_BOOLEAN),
            $request->description,
        );

        return response()->json(IncomeResource::collection($incomes));
    }

    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        Income::create($request->validated());

        return back();
    }

    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $income->update($request->validated());

        return back();
    }

    public function destroy(Income $income): RedirectResponse
    {
        $income->delete();

        return back();
    }
}
