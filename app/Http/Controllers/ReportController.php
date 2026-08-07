<?php

namespace App\Http\Controllers;

use App\Services\VatReportPdfService;
use App\Services\QapPdfService;
use App\Services\WTaxExpandedPdfService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url') ?? '', '/');
        $this->key = config('services.supabase.key') ?? '';
    }

    /**
     * Export VAT Summary Report (PDF)
     */
    public function exportVatPdf(Request $request, VatReportPdfService $vatReportPdfService)
    {
        $clientId  = $request->get('client_id', session('active_client_id'));
        $startDate = $request->get('start_date');

        // Derive year from start_date or default to current year
        $year = !empty($startDate) ? (int) Carbon::parse($startDate)->year : (int) date('Y');

        if (!$clientId) {
            return redirect()->back()->with('error', 'Please select a client.');
        }

        return $vatReportPdfService->generatePdf($clientId, $year);
    }

    /**
     * Export Quarterly Alphalist of Payees (QAP PDF)
     */
    public function exportQapPdf(Request $request, QapPdfService $qapPdfService)
    {
        $clientId = $request->get('client_id', session('active_client_id'));
        $quarter  = (int) $request->get('quarter', ceil(now()->month / 3));
        $year     = (int) $request->get('year', now()->year);

        if (!$clientId) {
            return redirect()->back()->with('error', 'Please select a client.');
        }

        return $qapPdfService->generatePdf($clientId, $quarter, $year);
    }

    public function exportWTaxExpandedPdf(Request $request, WTaxExpandedPdfService $wTaxPdfService)
    {
        $clientId  = $request->get('client_id', session('active_client_id'));
        $startDate = $request->get('start_date');

        $year = !empty($startDate) ? (int) Carbon::parse($startDate)->year : (int) date('Y');

        if (!$clientId) {
            return redirect()->back()->with('error', 'Please select a client.');
        }

        return $wTaxPdfService->generatePdf($clientId, $year);
    }
}