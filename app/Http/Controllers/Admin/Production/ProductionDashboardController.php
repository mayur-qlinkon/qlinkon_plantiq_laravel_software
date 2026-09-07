<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Services\Production\ProductionDashboardService;

class ProductionDashboardController extends Controller
{
    public function __construct(private ProductionDashboardService $dashboardService)
    {
    }

    // Owner-facing snapshot: today's activities, live plants/batches,
    // today's losses + harvest, assigned worker count, month-to-date harvest.
    public function index()
    {
        $stats = $this->dashboardService->getStats();
        $activeBatches = $this->dashboardService->activeBatches();
        $recentActivity = $this->dashboardService->recentActivity();
        $growingSpaces = $this->dashboardService->availableGrowingSpaces();

        return view('admin.production.dashboard', compact('stats', 'activeBatches', 'recentActivity', 'growingSpaces'));
    }
}