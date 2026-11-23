<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\TransactionResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * GET /api/v1/dashboard — home screen totals, recent entries and top dues.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->reportService->getDashboardData($request->user()->tenant_id);
        $m = $data['metrics'];

        return response()->json([
            'totals' => [
                'total_due' => $m['total_outstanding_balance'],        // Customers owe the shop
                'total_advance' => $m['total_advance_balance'],        // Shop holds customers' advance
                'today_udhaar' => $m['today_udhaar'],                  // Credit given today
                'today_collection' => $m['today_collection'],          // Payments received today
                'total_customers' => $m['total_customers'],
                'total_udhaar_given' => $m['total_udhaar_given'],
                'total_payments_received' => $m['total_payments_received'],
            ],
            'recent_transactions' => TransactionResource::collection($data['recent_transactions']),
            'top_due_customers' => CustomerResource::collection($data['highest_outstanding_customers']),
            'daily_collection_chart' => $data['daily_chart'],
        ]);
    }
}
