<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    protected $reportService;

    public function __construct(SalesReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        return view('admin.reports.sales_report', $data);
    }

    /**
     * Server-side PDF export (DomPDF). Honors the exact same filters as the
     * on-screen report — pass through the same query string (date_filter /
     * start_date / end_date) to get a matching PDF.
     */
    public function export(Request $request)
    {
        $data = $this->buildReportData($request);

        $pdf = Pdf::loadView('admin.reports.sales_report_pdf', $data)->setPaper('a4', 'portrait');

        $fileName = 'Sales_Report_'.str_replace(' ', '_', $data['filterLabel']).'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Shared data builder — used by both the on-screen dashboard and the PDF
     * export, so the two can never drift apart.
     */
    protected function buildReportData(Request $request): array
    {
        $storeIds = active_store_ids();

        $dateRange = $this->parseDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $filterLabel = $dateRange['label'];
        $activeFilter = $dateRange['filter'];

        $salesSummary = $this->reportService->getSalesSummary($storeIds, $startDate, $endDate);
        $topProducts = $this->reportService->getProductPerformance($storeIds, $startDate, $endDate, 10, 'desc');
        $lowProducts = $this->reportService->getProductPerformance($storeIds, $startDate, $endDate, 10, 'asc');
        $topCustomers = $this->reportService->getTopCustomers($storeIds, $startDate, $endDate, 10);
        $salesBySource = $this->reportService->getSalesBySource($storeIds, $startDate, $endDate);

        return compact(
            'salesSummary',
            'topProducts',
            'lowProducts',
            'topCustomers',
            'salesBySource',
            'startDate',
            'endDate',
            'filterLabel',
            'activeFilter'
        );
    }

    /**
     * Helper logic to calculate precise start and end dates based on user selection.
     */
    private function parseDateRange(Request $request)
    {
        $filter = $request->input('date_filter', 'this_month'); // Default to 'This Month'

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $label = 'This Month';

        switch ($filter) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today()->endOfDay();
                $label = 'Today';
                break;
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $label = 'This Week';
                break;
            case 'this_month':
                // Defaults already set
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $label = 'This Year';
                break;
            case 'custom':
                if ($request->filled(['start_date', 'end_date'])) {
                    $start = Carbon::parse($request->start_date)->startOfDay();
                    $end = Carbon::parse($request->end_date)->endOfDay();
                    $label = $start->format('d M Y').' to '.$end->format('d M Y');
                }
                break;
        }

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'label' => $label,
            'filter' => $filter,
        ];
    }
}