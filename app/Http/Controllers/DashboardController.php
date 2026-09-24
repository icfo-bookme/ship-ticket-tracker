<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $dashboardMetrics) {}

    public function index(): View
    {
        return view('dashboard', $this->dashboardMetrics->metrics());
    }
}
