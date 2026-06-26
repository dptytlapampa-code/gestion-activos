<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $dashboardMetricsService) {}

    public function __invoke(Request $request): View
    {
        return view('dashboard', $this->dashboardMetricsService->metricsFor($request));
    }
}
