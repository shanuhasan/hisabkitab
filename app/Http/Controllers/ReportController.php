<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Show the main reports dashboard with tabbed and filtered reporting views.
     */
    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $reportType = $request->input('report', 'period'); // 'period', 'customer', 'outstanding', 'collection'
        $period = $request->input('period', 'monthly'); // 'daily', 'weekly', 'monthly', 'custom'

        // Determine date ranges based on period shortcut
        $today = Carbon::today();
        [$fromDate, $toDate] = match ($period) {
            'daily' => [$today->toDateString(), $today->toDateString()],
            'weekly' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'monthly' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            default => [
                $request->input('from_date', $today->copy()->startOfMonth()->toDateString()),
                $request->input('to_date', $today->toDateString())
            ],
        };

        // Fetch data based on requested report type
        $periodData = $this->reportService->getPeriodReport($tenantId, $fromDate, $toDate);
        $customerData = $this->reportService->getCustomerWiseSummary($tenantId, $request->input('customer_search'));
        $outstandingData = $this->reportService->getOutstandingCustomers($tenantId);
        $collectionData = $this->reportService->getCollectionReport($tenantId, $fromDate, $toDate);

        return view('reports.index', compact(
            'reportType',
            'period',
            'fromDate',
            'toDate',
            'periodData',
            'customerData',
            'outstandingData',
            'collectionData'
        ));
    }

    /**
     * Export selected report as printable PDF using DomPDF.
     */
    public function exportPdf(Request $request): Response
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;
        $reportType = $request->input('report', 'period');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $data = match ($reportType) {
            'customer' => $this->reportService->getCustomerWiseSummary($tenantId),
            'outstanding' => $this->reportService->getOutstandingCustomers($tenantId),
            'collection' => $this->reportService->getCollectionReport($tenantId, $fromDate, $toDate),
            default => $this->reportService->getPeriodReport($tenantId, $fromDate, $toDate),
        };

        $pdf = Pdf::loadView('reports.pdf', [
            'tenant' => $tenant,
            'reportType' => $reportType,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'data' => $data,
        ]);

        $fileName = 'HisabKitab-Report-' . $reportType . '-' . date('Ymd-His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Export selected report as CSV file for Excel / Spreadsheet analysis.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;
        $reportType = $request->input('report', 'period');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $fileName = 'HisabKitab-' . $reportType . '-' . date('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($tenantId, $reportType, $fromDate, $toDate) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($reportType === 'customer') {
                $report = $this->reportService->getCustomerWiseSummary($tenantId);
                fputcsv($handle, ['Customer Name', 'Phone', 'Credit Limit (INR)', 'Total Udhaar (INR)', 'Total Jama (INR)', 'Current Balance (INR)', 'Last Transaction Date']);
                foreach ($report['customers'] as $cust) {
                    fputcsv($handle, [
                        $cust['name'],
                        $cust['phone'],
                        $cust['credit_limit'] ?? '0.00',
                        $cust['total_credit'],
                        $cust['total_payment'],
                        $cust['current_balance'],
                        $cust['last_transaction_at'],
                    ]);
                }
            } elseif ($reportType === 'outstanding') {
                $report = $this->reportService->getOutstandingCustomers($tenantId);
                fputcsv($handle, ['Customer Name', 'Phone', 'Address', 'Outstanding Udhaar (INR)', 'Credit Limit (INR)', 'Last Transaction Date']);
                foreach ($report['customers'] as $cust) {
                    fputcsv($handle, [
                        $cust->name,
                        $cust->phone,
                        $cust->address ?? 'N/A',
                        $cust->current_balance,
                        $cust->credit_limit ?? '0.00',
                        $cust->last_transaction_at ? $cust->last_transaction_at->format('d/m/Y') : 'N/A',
                    ]);
                }
            } elseif ($reportType === 'collection') {
                $report = $this->reportService->getCollectionReport($tenantId, $fromDate, $toDate);
                fputcsv($handle, ['Date', 'Txn Number', 'Customer Name', 'Phone', 'Payment Mode', 'Amount (INR)', 'Description']);
                foreach ($report['payments'] as $p) {
                    fputcsv($handle, [
                        $p->transaction_date->format('d/m/Y'),
                        $p->transaction_number,
                        $p->customer->name ?? 'N/A',
                        $p->customer->phone ?? 'N/A',
                        ucfirst($p->payment_mode),
                        $p->amount,
                        $p->description ?? '',
                    ]);
                }
            } else {
                $report = $this->reportService->getPeriodReport($tenantId, $fromDate, $toDate);
                fputcsv($handle, ['Date', 'Txn Number', 'Type', 'Customer Name', 'Phone', 'Payment Mode', 'Amount (INR)', 'Running Balance (INR)', 'Description']);
                foreach ($report['transactions'] as $txn) {
                    fputcsv($handle, [
                        $txn->transaction_date->format('d/m/Y'),
                        $txn->transaction_number,
                        ucfirst($txn->type),
                        $txn->customer->name ?? 'N/A',
                        $txn->customer->phone ?? 'N/A',
                        ucfirst($txn->payment_mode),
                        $txn->amount,
                        $txn->running_balance,
                        $txn->description ?? '',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}

