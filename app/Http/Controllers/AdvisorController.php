<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthYearRequest;
use App\Services\AdvisorService;
use Inertia\Inertia;
use Inertia\Response;

class AdvisorController extends Controller
{
    public function __construct(private AdvisorService $advisorService) {}

    public function index(MonthYearRequest $request): Response
    {
        $month = (int) $request->month;
        $year = (int) $request->year;

        return Inertia::render('advisor', [
            'insights' => $this->advisorService->getInsights($month, $year),
            'month' => $month,
            'year' => $year,
        ]);
    }
}
