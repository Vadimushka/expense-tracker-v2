<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthYearRequest;
use App\Services\PeriodService;
use Inertia\Inertia;
use Inertia\Response;

class SummaryController extends Controller
{
    public function __construct(private PeriodService $periodService) {}

    public function __invoke(MonthYearRequest $request): Response
    {
        $month = (int) $request->month;
        $year = (int) $request->year;

        return Inertia::render('summary', [
            'periods' => $this->periodService->buildPeriods($month, $year),
            'month' => $month,
            'year' => $year,
        ]);
    }
}
