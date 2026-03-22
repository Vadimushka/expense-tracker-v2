<?php

namespace App\Http\Controllers;

use App\Http\Requests\YearRequest;
use App\Services\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analyticsService) {}

    public function index(YearRequest $request): Response
    {
        $year = (int) $request->year;

        return Inertia::render('analytics', [
            'analytics' => $this->analyticsService->getYearlyAnalytics($year),
            'year' => $year,
        ]);
    }
}
